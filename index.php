<?php
require_once __DIR__ . '/includes/bootstrap.php';
?><!doctype html>
<html lang="pt-br">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Admin Leads - Captura & RD Station</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
  <link rel="stylesheet" href="public/assets/styles.css" />
</head>
<body>
<div class="container-fluid py-3">
  <div class="d-flex align-items-center justify-content-between mb-3">
    <h1 class="h4 mb-0">Leads</h1>
    <div>
      <a id="btnExport" class="btn btn-outline-secondary btn-sm" href="#">Exportar CSV</a>
      <button id="btnSendSelected" class="btn btn-primary btn-sm">Enviar Selecionados</button>
    </div>
  </div>

  <div class="row">
    <div class="col-lg-7">
      <div class="card mb-3">
        <div class="card-body">
          <form id="filters" class="row g-2">
            <div class="col-md-3">
              <input type="text" name="q" class="form-control" placeholder="Buscar..." />
            </div>
            <div class="col-md-2">
              <input type="text" name="city" class="form-control" placeholder="Cidade" />
            </div>
            <div class="col-md-2">
              <select name="source" class="form-select">
                <option value="">Fonte</option>
                <option value="webhook">Webhook</option>
                <option value="manual">Manual</option>
              </select>
            </div>
            <div class="col-md-2">
              <select name="verified" class="form-select">
                <option value="">Verificado?</option>
                <option value="1">Sim</option>
                <option value="0">Não</option>
              </select>
            </div>
            <div class="col-md-2">
              <select name="has_whatsapp" class="form-select">
                <option value="">WhatsApp?</option>
                <option value="1">Com</option>
                <option value="0">Sem</option>
              </select>
            </div>
            <div class="col-md-1">
              <button class="btn btn-secondary w-100" type="submit">Filtrar</button>
            </div>
          </form>
        </div>
      </div>

      <div class="card">
        <div class="table-responsive">
          <table class="table table-hover align-middle" id="leadsTable">
            <thead>
              <tr>
                <th><input type="checkbox" id="checkAll" /></th>
                <th>Nome</th>
                <th>Telefone</th>
                <th>WhatsApp</th>
                <th>Cidade</th>
                <th>Rating</th>
                <th>Fonte</th>
                <th>Verificado</th>
              </tr>
            </thead>
            <tbody></tbody>
          </table>
        </div>
        <div class="card-footer d-flex justify-content-between align-items-center">
          <div id="pagination"></div>
          <div id="stats" class="text-muted small"></div>
        </div>
      </div>
    </div>
    <div class="col-lg-5">
      <div class="card h-100">
        <div class="card-header">Mapa</div>
        <div class="card-body p-0">
          <div id="map" style="height: 500px;"></div>
        </div>
      </div>
    </div>
  </div>

  <div class="row mt-3">
    <div class="col-lg-12">
      <div class="card">
        <div class="card-header">Configuração</div>
        <div class="card-body">
          <form id="configForm" class="row g-2">
            <div class="col-md-3"><input class="form-control" name="RD_CLIENT_ID" placeholder="RD_CLIENT_ID"></div>
            <div class="col-md-3"><input class="form-control" name="RD_CLIENT_SECRET" placeholder="RD_CLIENT_SECRET"></div>
            <div class="col-md-3"><input class="form-control" name="RD_REDIRECT_URI" placeholder="RD_REDIRECT_URI"></div>
            <div class="col-md-3"><input class="form-control" name="RD_REFRESH_TOKEN" placeholder="RD_REFRESH_TOKEN"></div>
            <div class="col-md-3"><input class="form-control" name="API_KEY" placeholder="API_KEY"></div>
            <div class="col-md-2"><button class="btn btn-outline-primary w-100" type="submit">Salvar</button></div>
          </form>
        </div>
      </div>
    </div>
  </div>

  <!-- Modal Detalhe/Edição -->
  <div class="modal fade" id="leadModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">Lead</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <form id="leadForm" class="row g-2"></form>
        </div>
        <div class="modal-footer">
          <button class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button>
          <button id="btnSaveLead" class="btn btn-primary">Salvar</button>
        </div>
      </div>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script src="public/assets/map.js"></script>
<script src="public/assets/app.js"></script>
</body>
</html>