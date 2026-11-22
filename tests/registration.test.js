const test = require('node:test');
const assert = require('node:assert/strict');
const fs = require('node:fs');
const vm = require('node:vm');
const path = require('node:path');
const { register, install } = require('../public/registration');

function portal(appId, options = {}) {
  const state = { tabs: [], types: [], fields: { lead: [], deal: [] }, calls: [], finished: 0 };
  let failure = options.fail;
  const bx = {
    installFinish() { state.finished++; state.calls.push({ method: 'installFinish' }); },
    callMethod(method, params, callback) {
      state.calls.push({ method, params: structuredClone(params) });
      if (failure === method) {
        failure = null;
        callback({ error: () => ({ ex: { error: 'ACCESS_DENIED', error_description: 'Access denied' } }) });
        return;
      }
      let result;
      switch (method) {
        case 'app.info': result = { ID: appId }; break;
        case 'placement.get': result = structuredClone(state.tabs); break;
        case 'placement.bind':
          if (!options.dropTab) state.tabs.push({ placement: params.PLACEMENT, handler: params.HANDLER, userId: params.USER_ID });
          result = true; break;
        case 'userfieldtype.list': result = structuredClone(state.types); break;
        case 'userfieldtype.add':
          state.types.push(structuredClone(params)); result = true; break;
        case 'userfieldtype.update':
          Object.assign(state.types.find(x => x.USER_TYPE_ID === params.USER_TYPE_ID), params);
          result = true; break;
        default: {
          const match = /^crm\.(lead|deal)\.userfield\.(list|add|update)$/.exec(method);
          assert.ok(match, 'Unexpected method: ' + method);
          const rows = state.fields[match[1]];
          if (match[2] === 'list') result = structuredClone(rows);
          if (match[2] === 'add') {
            rows.push({ ID: rows.length + 1, ...structuredClone(params.fields) });
            result = rows.length;
          }
          if (match[2] === 'update') {
            Object.assign(rows.find(x => x.ID === params.id), params.fields);
            result = true;
          }
        }
      }
      callback({ error: () => null, data: () => result, more: () => false });
    }
  };
  return { bx, state };
}

test('two clean portals register all placements and their own fields before installFinish', async () => {
  for (const id of [101, 202]) {
    const { bx, state } = portal(id);
    await install(bx, 'https://app.example');
    assert.equal(state.finished, 1);
    assert.equal(state.calls.at(-1).method, 'installFinish');
    assert.deepEqual(state.tabs.map(x => x.placement), ['CRM_LEAD_DETAIL_TAB', 'CRM_DEAL_DETAIL_TAB']);
    assert.ok(state.tabs.every(x => x.handler === 'https://app.example/field' && x.userId === 0));
    assert.equal(state.types[0].USER_TYPE_ID, 'candidate_time');
    for (const entity of ['lead', 'deal']) {
      assert.equal(state.fields[entity][0].USER_TYPE_ID, 'rest_' + id + '_candidate_time');
    }
    assert.ok(!state.calls.some(x => x.method === 'placement.bind' && x.params.PLACEMENT === 'USERFIELD_TYPE'));
  }
});

test('repeated registration preserves IDs, values and options without duplicate fields or tabs', async () => {
  const { bx, state } = portal(101);
  await install(bx, 'https://app.example');
  state.fields.lead[0].value = 'existing data';
  await register(bx, 'https://app.example');
  assert.equal(state.finished, 1);
  assert.equal(state.tabs.length, 2);
  assert.equal(state.types.length, 1);
  assert.equal(state.fields.lead.length, 1);
  assert.equal(state.fields.deal.length, 1);
  assert.equal(state.fields.lead[0].ID, 1);
  assert.equal(state.fields.lead[0].value, 'existing data');
  assert.ok(!state.calls.some(x => x.method === 'app.option.set'));
});

test('failed REST call does not finish installation and retry repairs partial state', async () => {
  const { bx, state } = portal(101, { fail: 'crm.deal.userfield.add' });
  await assert.rejects(install(bx, 'https://app.example'), /Access denied/);
  assert.equal(state.finished, 0);
  assert.equal(state.fields.lead.length, 1);
  await install(bx, 'https://app.example');
  assert.equal(state.finished, 1);
  assert.equal(state.tabs.length, 2);
  assert.equal(state.fields.lead.length, 1);
  assert.equal(state.fields.deal.length, 1);
});

test('missing verified registration blocks installFinish even if bind reports success', async () => {
  const { bx, state } = portal(101, { dropTab: true });
  await assert.rejects(install(bx, 'https://app.example'), /CRM_LEAD_DETAIL_TAB/);
  assert.equal(state.finished, 0);
});

test('a field of another type is preserved and requires explicit resolution', async () => {
  const { bx, state } = portal(101);
  const field = { ID: 55, FIELD_NAME: 'UF_CRM_CAND_TIME', USER_TYPE_ID: 'string', value: 'keep' };
  state.fields.lead.push(structuredClone(field));
  await assert.rejects(install(bx, 'https://app.example'), /другим типом/);
  assert.equal(state.finished, 0);
  assert.deepEqual(state.fields.lead[0], field);
});

test('migration updates type handler and binds new tabs without removing existing ones', async () => {
  const { bx, state } = portal(101);
  await register(bx, 'https://old.example');
  await register(bx, 'https://new.example');
  assert.equal(state.types[0].HANDLER, 'https://new.example/field');
  assert.equal(state.fields.lead.length, 1);
  assert.equal(state.fields.deal.length, 1);
  assert.equal(state.tabs.filter(x => x.handler === 'https://new.example/field').length, 2);
});

test('type lookup follows subsequent pages rather than trying to add an existing type', async () => {
  const { bx, state } = portal(101);
  await register(bx, 'https://app.example');
  const original = bx.callMethod;
  bx.callMethod = function (method, params, callback) {
    if (method !== 'userfieldtype.list') return original(method, params, callback);
    callback({ error: () => null, data: () => [], more: () => true,
      next: receive => receive({ error: () => null, data: () => state.types, more: () => false }) });
  };
  await register(bx, 'https://app.example');
  assert.equal(state.types.length, 1);
});

test('a synchronous SDK error blocks completion', async () => {
  const { bx, state } = portal(101);
  bx.callMethod = () => { throw new Error('SDK unavailable'); };
  await assert.rejects(install(bx, 'https://app.example'), /SDK unavailable/);
  assert.equal(state.finished, 0);
});

test('deal detail tab uses placement.info().placement, not the default lead', async () => {
  const source = fs.readFileSync(path.join(__dirname, '../api/field.php'), 'utf8');
  const inline = [...source.matchAll(/<script>([\s\S]*?)<\/script>/g)][0][1];
  const calls = [];
  let ready;
  const context = {
    document: { getElementById: () => ({}) },
    BX24: {
      init: callback => { ready = callback; },
      placement: { info: () => ({ placement: 'CRM_DEAL_DETAIL_TAB', options: { ID: '42' } }) },
      callMethod: (method, params, callback) => {
        calls.push({ method, params });
        const data = method === 'app.option.get' ? { dealCityField: 'ADDRESS_CITY', leadCityField: 'WRONG_FIELD' } : {};
        callback({ error: () => null, data: () => data });
      }
    }, setTimeout, clearTimeout, console
  };
  vm.runInNewContext(inline, context);
  await ready();
  assert.ok(calls.some(x => x.method === 'crm.deal.get' && x.params.id === '42'));
  assert.ok(!calls.some(x => x.method === 'crm.lead.get'));
});
