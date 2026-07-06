/**
 * Taskvel API client
 * ---------------------------------------------------------------
 * Drop-in replacement for the old localStorage persistence layer.
 * Every function returns a Promise resolving to parsed JSON.
 *
 * Mapping from the original vanilla-JS app to this client:
 *   load()                 -> Taskvel.tasks.list()
 *   save(tasks)             -> no longer needed — every mutation below
 *                              (create/update/delete) persists immediately
 *   addTask(data)            -> Taskvel.tasks.create(data)
 *   delTask(id)              -> Taskvel.tasks.remove(id)
 *   markDone(id)/markUndone  -> Taskvel.tasks.update(id, {status:'done'|'todo'})
 *   saveEdit(id, data)       -> Taskvel.tasks.update(id, data)
 *   togglePin(id)            -> Taskvel.tasks.update(id, {pinned:1})
 *   bulkDone/bulkDelete      -> Taskvel.tasks.bulk(ids, 'done'|'delete')
 *   renderMatrix()           -> Taskvel.tasks.matrix()
 *   addRemark/renderRemarks  -> Taskvel.remarks.add()/list()
 *   saveTemplates/loadTempl. -> Taskvel.templates.create()/list()
 *   startTimeTracking/stop   -> Taskvel.timer.start()/stop()
 *   logFocusMinute (pomodoro)-> Taskvel.timer.focusLog()
 *   saveStreak/updateStreak  -> handled server-side automatically on focusLog()
 *   pushNotification/panel   -> Taskvel.notifications.list()/markSeen()
 *   exportCSV/exportPDF      -> Taskvel.export.csv() / window.open for pdf route
 *   share a task via email   -> Taskvel.share.invite(taskId, email, permission)
 */
const Taskvel = (() => {
    const base = ''; // same-origin; set e.g. '/taskvel' if hosted in a subfolder

    function csrfToken() {
        const meta = document.querySelector('meta[name="csrf-token"]');
        return meta ? meta.getAttribute('content') : '';
    }

    async function request(path, { method = 'GET', body } = {}) {
        const headers = body ? { 'Content-Type': 'application/json' } : {};
        if (method !== 'GET') headers['X-CSRF-Token'] = csrfToken();
        const res = await fetch(base + path, {
            method,
            headers,
            body: body ? JSON.stringify(body) : undefined,
            credentials: 'same-origin',
        });
        const data = await res.json().catch(() => ({}));
        if (!res.ok) throw new Error(data.error || `Request failed (${res.status})`);
        return data;
    }

    return {
        // Generic escape hatch for endpoints that don't have a dedicated namespace
        // (used by teams.php / team.php / project.php).
        request,

        auth: {
            me:      () => request('/api/auth.php?action=me'),
            login:   (email, password) => request('/api/auth.php?action=login', { method: 'POST', body: { email, password } }),
            register:(name, email, password) => request('/api/auth.php?action=register', { method: 'POST', body: { name, email, password } }),
            logout:  () => request('/api/auth.php?action=logout', { method: 'POST' }),
        },

        tasks: {
            list:   (filters = {}) => request('/api/tasks.php?action=list&' + new URLSearchParams(filters)),
            show:   (id) => request(`/api/tasks.php?action=show&id=${id}`),
            create: (data) => request('/api/tasks.php?action=create', { method: 'POST', body: data }),
            update: (id, data) => request(`/api/tasks.php?action=update&id=${id}`, { method: 'PUT', body: data }),
            remove: (id) => request(`/api/tasks.php?action=delete&id=${id}`, { method: 'DELETE' }),
            bulk:   (ids, op) => request('/api/tasks.php?action=bulk', { method: 'POST', body: { ids, op } }),
            matrix: () => request('/api/tasks.php?action=matrix'),
        },

        remarks: {
            list:   (taskId) => request(`/api/remarks.php?action=list&task_id=${taskId}`),
            add:    (taskId, body) => request('/api/remarks.php?action=add', { method: 'POST', body: { task_id: taskId, body } }),
            draft:  (taskId, body) => request('/api/remarks.php?action=draft', { method: 'POST', body: { task_id: taskId, body } }),
            remove: (id) => request(`/api/remarks.php?action=delete&id=${id}`, { method: 'DELETE' }),
        },

        share: {
            invite:       (taskId, email, permission = 'edit') =>
                request('/api/share.php?action=invite', { method: 'POST', body: { task_id: taskId, email, permission } }),
            list:         (taskId) => request(`/api/share.php?action=list&task_id=${taskId}`),
            sharedWithMe: () => request('/api/share.php?action=shared-with-me'),
            revoke:       (shareId) => request('/api/share.php?action=revoke', { method: 'POST', body: { share_id: shareId } }),
        },

        notifications: {
            list:        () => request('/api/notifications.php?action=list'),
            markSeen:    () => request('/api/notifications.php?action=mark-seen', { method: 'POST' }),
            unreadCount: () => request('/api/notifications.php?action=unread-count'),
        },

        timer: {
            start:        (taskId, tag) => request('/api/timer.php?action=start', { method: 'POST', body: { task_id: taskId, tag } }),
            stop:         (id) => request('/api/timer.php?action=stop', { method: 'POST', body: { id } }),
            report:       (days = 7) => request(`/api/timer.php?action=report&days=${days}`),
            focusLog:     (data) => request('/api/timer.php?action=focus-log', { method: 'POST', body: data }),
            focusHistory: () => request('/api/timer.php?action=focus-history'),
            streak:       () => request('/api/timer.php?action=streak'),
        },

        templates: {
            list:   () => request('/api/templates.php?action=list'),
            create: (name, payload) => request('/api/templates.php?action=create', { method: 'POST', body: { name, payload } }),
            remove: (id) => request(`/api/templates.php?action=delete&id=${id}`, { method: 'DELETE' }),
        },

        export: {
            csv:  () => window.open('/api/export.php?format=csv', '_blank'),
            json: () => request('/api/export.php?action=export&format=json'),
        },

        settings: {
            calendarLink: () => request('/api/settings.php?action=calendar-link'),
            touchDevice:  (label) => request('/api/settings.php?action=touch-device', { method: 'POST', body: { device_label: label } }),
            devices:      () => request('/api/settings.php?action=devices'),
            pushSubscribe:(subscription, label) => request('/api/settings.php?action=push-subscribe', { method: 'POST', body: { subscription, device_label: label } }),
            vapidPublicKey: () => request('/api/settings.php?action=vapid-public-key'),
        },

        attachments: {
            list:   (taskId) => request(`/api/attachments.php?action=list&task_id=${taskId}`),
            remove: (id) => request(`/api/attachments.php?action=delete&id=${id}`, { method: 'DELETE' }),
            upload: async (taskId, file) => {
                const fd = new FormData();
                fd.append('task_id', taskId);
                fd.append('file', file);
                const res = await fetch('/api/attachments.php?action=upload', { method: 'POST', body: fd, credentials: 'same-origin' });
                const data = await res.json();
                if (!res.ok) throw new Error(data.error || 'Upload failed');
                return data;
            },
        },

        // Full client-state blob sync — used by the original single-file
        // Taskvel app to mirror its entire localStorage state to the account
        // so it's identical on every device, without remapping every field
        // into relational tables.
        state: {
            pull: () => request('/api/state.php?action=pull'),
            push: (data) => request('/api/state.php?action=push', { method: 'POST', body: { state: data } }),
        },
    };
})();
