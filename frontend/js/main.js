const API_BASE = 'http://localhost:8000/api';

async function checkApi() {
    const statusEl = document.getElementById('status');
    const listEl = document.getElementById('endpoints');

    try {
        const res = await fetch(`${API_BASE}`);
        if (!res.ok) throw new Error(`HTTP ${res.status}`);
        const data = await res.json();

        statusEl.textContent = `OK — ${data.name} v${data.version} (Phase ${data.phase})`;
        for (const ep of data.endpoints) {
            const li = document.createElement('li');
            const code = document.createElement('code');
            code.textContent = ep;
            li.appendChild(code);
            listEl.appendChild(li);
        }
    } catch (err) {
        statusEl.textContent = `Could not connect to API: ${err.message}. Is the backend running?`;
    }
}

checkApi();
