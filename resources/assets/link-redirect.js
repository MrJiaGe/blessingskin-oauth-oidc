(function() {
    function checkPendingLink() {
        fetch('/oidc/link/check-pending')
            .then(function(response) {
                return response.json();
            })
            .then(function(data) {
                if (data.has_pending && data.token) {
                    window.location.href = '/oidc/link/complete?token=' + data.token;
                }
            })
            .catch(function(error) {
                console.error('OIDC: Failed to check pending link', error);
            });
    }

    function onBlessingReady(callback) {
        if (typeof blessing !== 'undefined' && blessing.event) {
            blessing.event.on('mounted', callback);
        }

        var executed = false;
        var wrappedCallback = function() {
            if (executed) return;
            executed = true;
            callback();
        };

        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', wrappedCallback);
        } else {
            wrappedCallback();
        }
    }

    onBlessingReady(function() {
        checkPendingLink();
    });
})();
