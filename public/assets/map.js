// Inicialização do Leaflet e sincronização com seleção de leads
let map, markers = {}, selectedMarkerId = null;

function initMap() {
  map = L.map('map').setView([-14.235, -51.925], 4); // Brasil
  L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    maxZoom: 18,
    attribution: '&copy; OpenStreetMap'
  }).addTo(map);
}

function clearMarkers() {
  Object.values(markers).forEach(m => map.removeLayer(m));
  markers = {};
}

function addMarkers(leads) {
  clearMarkers();
  leads.forEach(lead => {
    if (lead.lat && lead.lng) {
      const marker = L.marker([lead.lat, lead.lng]).addTo(map);
      marker.on('click', () => {
        const row = document.querySelector(`tr[data-id="${lead.id}"]`);
        if (row) row.classList.add('table-primary');
        if (selectedMarkerId && markers[selectedMarkerId]) markers[selectedMarkerId].getElement()?.classList.remove('leaflet-marker-selected');
        selectedMarkerId = lead.id;
        marker.getElement()?.classList.add('leaflet-marker-selected');
        map.panTo([lead.lat, lead.lng]);
      });
      markers[lead.id] = marker;
    }
  });
}

document.addEventListener('DOMContentLoaded', initMap);