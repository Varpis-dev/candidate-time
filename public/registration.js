(function (root) {
  'use strict';

  const TITLE = 'Время клиента К2';
  const TYPE = 'candidate_time';
  const FIELD = 'UF_CRM_CAND_TIME';
  const TABS = ['CRM_LEAD_DETAIL_TAB', 'CRM_DEAL_DETAIL_TAB'];

  function call(bx, method, params = {}, list = false) {
    return new Promise((resolve, reject) => {
      const timer = setTimeout(() => reject(new Error('Таймаут: ' + method)), 30000);
      const rows = [];
      function receive(result) {
        try {
          if (!result) throw new Error(method + ': пустой ответ');
          if (result.error()) {
            const error = result.error();
            const details = error.ex || {};
            throw new Error(method + ': ' + (details.error_description || details.error || String(error)));
          }
          const data = result.data();
          if (data === false || data == null) throw new Error(method + ': операция не выполнена');
          if (list) {
            if (!Array.isArray(data)) throw new Error(method + ': ожидался список');
            rows.push(...data);
            if (result.more && result.more()) {
              result.next(receive);
              return;
            }
          }
          clearTimeout(timer);
          resolve(list ? rows : data);
        } catch (error) {
          clearTimeout(timer);
          reject(error);
        }
      }
      try {
        bx.callMethod(method, params, receive);
      } catch (error) {
        clearTimeout(timer);
        reject(error);
      }
    });
  }

  function hasTab(rows, placement, handler) {
    return rows.some(row =>
      (row.placement || row.PLACEMENT) === placement &&
      (row.handler || row.HANDLER) === handler &&
      Number(row.userId ?? row.USER_ID ?? 0) === 0
    );
  }

  // Everything is registered in the current app/portal context, before installFinish.
  // No portal IDs or success flags are stored in localStorage.
  async function register(bx, origin, progress = function () {}) {
    const handler = new URL('/field', origin).href;
    progress('Регистрируем вкладки лида и сделки…');
    const existing = await call(bx, 'placement.get');
    for (const placement of TABS) {
      if (!hasTab(existing, placement, handler)) {
        await call(bx, 'placement.bind', {
          PLACEMENT: placement, HANDLER: handler, TITLE, USER_ID: 0
        });
      }
    }

    progress('Регистрируем тип поля…');
    const types = await call(bx, 'userfieldtype.list', {}, true);
    const type = types.find(row => row.USER_TYPE_ID === TYPE);
    // USERFIELD_TYPE is registered by userfieldtype.add, not placement.bind.
    await call(bx, type ? 'userfieldtype.update' : 'userfieldtype.add', {
      USER_TYPE_ID: TYPE, HANDLER: handler, TITLE,
      DESCRIPTION: 'Текущее время клиента по его городу и региону',
      OPTIONS: { height: 180 }
    });

    const app = await call(bx, 'app.info');
    const appId = app.ID || app.APP_ID;
    if (!appId) throw new Error('Не удалось определить ID приложения');
    const fullType = 'rest_' + appId + '_' + TYPE;
    const labels = { EDIT_FORM_LABEL: TITLE, LIST_COLUMN_LABEL: TITLE, LIST_FILTER_LABEL: TITLE };

    progress('Проверяем поля в лидах и сделках…');
    for (const entity of ['lead', 'deal']) {
      const prefix = 'crm.' + entity + '.userfield.';
      const fields = await call(bx, prefix + 'list', { filter: { FIELD_NAME: FIELD } }, true);
      const field = fields.find(row => row.FIELD_NAME === FIELD);
      if (field) {
        // Never replace a field or its data, including a field belonging to another app.
        if (field.USER_TYPE_ID !== fullType) {
          throw new Error('Поле ' + FIELD + ' (' + entity + ') уже существует с другим типом. Обратитесь в поддержку приложения.');
        }
        await call(bx, prefix + 'update', { id: field.ID, fields: labels });
      } else {
        await call(bx, prefix + 'add', { fields: {
          ...labels, FIELD_NAME: FIELD, USER_TYPE_ID: fullType,
          MULTIPLE: 'N', MANDATORY: 'N', SHOW_FILTER: 'N', EDIT_IN_LIST: 'N', SORT: 100
        } });
      }
    }

    progress('Проверяем регистрацию встройки…');
    const registered = await call(bx, 'placement.get');
    for (const placement of TABS) {
      if (!hasTab(registered, placement, handler)) throw new Error('Не зарегистрирована встройка ' + placement);
    }
    const registeredTypes = await call(bx, 'userfieldtype.list', {}, true);
    if (!registeredTypes.some(row => row.USER_TYPE_ID === TYPE && row.HANDLER === handler)) {
      throw new Error('Не зарегистрирована встройка USERFIELD_TYPE');
    }
    return { handler, userTypeId: fullType };
  }

  async function install(bx, origin, progress) {
    const result = await register(bx, origin, progress);
    bx.installFinish();
    return result;
  }

  const api = { register, install };
  if (typeof module !== 'undefined' && module.exports) module.exports = api;
  else root.CandidateTimeRegistration = api;
})(typeof window !== 'undefined' ? window : globalThis);
