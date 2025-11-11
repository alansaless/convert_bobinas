// UI Admin: lista, filtros, seleção, envio e modal
const api = {
  async leads(params = {}) {
    const qs = new URLSearchParams(params).toString();
    const res = await fetch(`/api/leads?${qs}`);
    return res.json();
  },
  async stats() {
    const res = await fetch('/api/stats');
    return res.json();
  },
  async send(ids) {
    const res = await fetch('/api/lead/send', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ ids }) });
    return res.json();
  },
  async getLead(id) {
    const res = await fetch(`/api/lead/${id}`);
    return res.json();
  },
  async updateLead(id, payload) {
    const res = await fetch(`/api/lead/${id}/update`, { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(payload) });
    return res.json();
  },
  async saveConfig(payload) {
    const res = await fetch('/api/config/save', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(payload) });
    return res.json();
  }
};

let state = { page: 1, limit: 20, q: '', city: '', source: '', verified: '', has_whatsapp: '' };

function renderTable(data) {
  const tbody = document.querySelector('#leadsTable tbody');
  tbody.innerHTML = '';
  data.forEach(row => {
    const tr = document.createElement('tr');
    tr.dataset.id = row.id;
    tr.innerHTML = `
      <td><input type="checkbox" class="row-check" value="${row.id}"></td>
      <td><a href="#" class="lead-link">${row.name || ''}</a></td>
      <td>${row.phone || ''}</td>
      <td>${row.whatsapp || ''}</td>
      <td>${row.city || ''}</td>
      <td>${row.rating ?? ''}</td>
      <td>${row.source || ''}</td>
      <td>${row.verified ? 'Sim' : 'Não'}</td>
    `;
    tbody.appendChild(tr);
  });
}

function renderPagination(page, limit, total) {
  const el = document.getElementById('pagination');
  const pages = Math.ceil(total / limit) || 1;
  el.innerHTML = `Página ${page} de ${pages}`;
}

async function load() {
  const res = await api.leads(state);
  renderTable(res.data);
  renderPagination(res.page, res.limit, res.total);
  addMarkers(res.data);
  const st = await api.stats();
  document.getElementById('stats').innerText = `WhatsApp: ${st.with_whatsapp} | Sem: ${st.without_whatsapp} | Rating médio: ${st.avg_rating} | Verificados: ${st.verified}`;
  const qs = new URLSearchParams(state).toString();
  document.getElementById('btnExport').href = `/api/export?${qs}`;
}

document.addEventListener('DOMContentLoaded', () => {
  load();

  document.getElementById('filters').addEventListener('submit', (e) => {
    e.preventDefault();
    const form = e.target;
    ['q','city','source','verified','has_whatsapp'].forEach(k => state[k] = form[k].value);
    state.page = 1;
    load();
  });

  document.getElementById('checkAll').addEventListener('change', (e) => {
    document.querySelectorAll('.row-check').forEach(ch => ch.checked = e.target.checked);
  });

  document.getElementById('btnSendSelected').addEventListener('click', async () => {
    const ids = Array.from(document.querySelectorAll('.row-check')).filter(ch => ch.checked).map(ch => parseInt(ch.value, 10));
    if (!ids.length) return alert('Selecione pelo menos um lead.');
    const res = await api.send(ids);
    alert('Envio concluído. Veja logs na base.');
    load();
  });

  document.querySelector('#leadsTable').addEventListener('click', async (e) => {
    const link = e.target.closest('.lead-link');
    if (!link) return;
    e.preventDefault();
    const tr = link.closest('tr');
    const id = tr.dataset.id;
    const lead = await api.getLead(id);
    const form = document.getElementById('leadForm');
    form.innerHTML = '';
    const fields = ['name','email','phone','whatsapp','address','city','state','country','lat','lng','website','rating','reviews_count','category','source'];
    fields.forEach(f => {
      const val = lead[f] ?? '';
      const group = document.createElement('div');
      group.className = 'col-md-4';
      group.innerHTML = `<label class="form-label">${f}</label><input class="form-control" name="${f}" value="${val}">`;
      form.appendChild(group);
    });
    form.dataset.id = id;
    const modal = new bootstrap.Modal(document.getElementById('leadModal'));
    modal.show();
  });

  document.getElementById('btnSaveLead').addEventListener('click', async () => {
    const form = document.getElementById('leadForm');
    const id = form.dataset.id;
    const payload = {};
    Array.from(form.querySelectorAll('input')).forEach(inp => payload[inp.name] = inp.value);
    await api.updateLead(id, payload);
    alert('Salvo!');
    load();
    bootstrap.Modal.getInstance(document.getElementById('leadModal')).hide();
  });

  document.getElementById('configForm').addEventListener('submit', async (e) => {
    e.preventDefault();
    const form = e.target;
    const payload = {};
    Array.from(form.querySelectorAll('input')).forEach(inp => { if (inp.value) payload[inp.name] = inp.value; });
    await api.saveConfig(payload);
    alert('Configuração salva (arquivo .env atualizado)');
  });
});