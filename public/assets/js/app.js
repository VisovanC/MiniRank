(function () {
    const btn = document.getElementById('refresh-btn');
    const status = document.getElementById('refresh-status');
    if (!btn || !status) {
        return;
    }

    btn.addEventListener('click', async () => {
        btn.disabled = true;
        btn.textContent = 'Refreshing…';
        try {
            const res = await fetch('refresh.php', {
                method: 'POST',
                headers: { 'Accept': 'application/json' }
            });
            if (!res.ok) {
                throw new Error('HTTP ' + res.status);
            }
            const data = await res.json();
            if (!data.ok) {
                throw new Error(data.error || 'Refresh failed');
            }
            updateTable(data.rows);
            const keywordWord = data.count === 1 ? 'keyword' : 'keywords';
            status.textContent = 'Refreshed ' + data.count + ' ' + keywordWord
                + ' for ' + data.date + ' — ' + new Date().toLocaleTimeString();
        } catch (err) {
            status.textContent = 'Refresh failed: ' + err.message;
        } finally {
            btn.disabled = false;
            btn.textContent = 'Refresh positions';
        }
    });

    function updateTable(rows) {
        if (!Array.isArray(rows)) {
            return;
        }
        rows.forEach(function (row) {
            const tr = document.querySelector('tr[data-keyword-id="' + row.id + '"]');
            if (!tr) {
                return;
            }
            const posCell = tr.querySelector('.pos');
            if (posCell) {
                posCell.textContent = row.position === null ? '—' : String(row.position);
            }
            const trendCell = tr.querySelector('.trend');
            if (trendCell) {
                trendCell.className = 'trend' + (row.trend ? ' ' + row.trend : '');
                trendCell.textContent = row.trend_label;
            }
        });
    }
})();