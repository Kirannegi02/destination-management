@extends('admin.layouts.app')
@section('title', 'Edit zone & pricing')
@section('page-title', 'Edit: '.$zone->name)
@section('content')
@php
    $polyStr = old('zone.polygon_json', $zone->polygon ? json_encode($zone->polygon) : '');
    $citiesStr = old('zone.cities_text');
    if (!is_string($citiesStr)) {
        $citiesStr = implode("\n", $zone->cities ?? []);
    }
@endphp
<div class="card">
    <div class="card-header" style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:8px;">
        <h2 class="card-title">Edit zone package</h2>
        <a href="{{ route('admin.transports.index') }}" style="color:#667eea; text-decoration:none;">Back</a>
    </div>
    @if($errors->any())
    <div style="background:#f8d7da; padding:12px; margin:20px; border-radius:6px;">
        <ul style="margin:0;">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
    </div>
    @endif
    <form method="POST" action="{{ route('admin.transports.update', $zoneTransports->first()->id) }}" id="zone-bundle-form" style="padding:20px;">
        @csrf
        @method('PUT')

        <h3 style="margin:0 0 16px; font-size:16px;">Zone</h3>
        <div style="margin-bottom:14px;">
            <label style="font-weight:600;">Zone name *</label>
            <input type="text" name="zone[name]" value="{{ old('zone.name', $zone->name) }}" required style="width:100%; max-width:480px; padding:10px; border:2px solid #e2e8f0; border-radius:8px;">
        </div>
        <div style="margin-bottom:14px;">
            <label style="font-weight:600;">Places in this zone</label>
            <p style="margin:4px 0 6px; font-size:13px; color:#718096;">Draw or edit the polygon, then <strong>Fill cities from zone</strong>, or unlock to type.</p>
            <textarea name="zone[cities_text]" id="zone_cities_text" rows="3" readonly style="width:100%; max-width:560px; padding:10px; border:2px solid #e2e8f0; border-radius:8px; background:#f7fafc;">{{ $citiesStr }}</textarea>
            <div style="margin-top:8px; display:flex; flex-wrap:wrap; gap:8px; align-items:center;">
                <button type="button" id="btn-fill-cities-polygon" style="padding:8px 14px; background:#48bb78; color:white; border:none; border-radius:6px; cursor:pointer;">Fill cities from zone</button>
                <button type="button" id="btn-suggest-city" style="padding:8px 14px; background:#edf2f7; border:1px solid #cbd5e0; border-radius:6px; cursor:pointer;">Add place from map center</button>
                <label style="font-size:13px; cursor:pointer; user-select:none;"><input type="checkbox" id="zone_cities_unlock"> Allow manual edit</label>
            </div>
        </div>
        <div style="margin-bottom:14px;">
            <label style="font-weight:600;">Zone boundary</label>
            <div style="position:relative; max-width:900px;">
                <div id="zone-map-toolbar" style="position:absolute; z-index:1000; left:10px; top:48px; display:flex; gap:4px;">
                    <button type="button" id="btn-layer-road" style="padding:6px 10px; border-radius:6px; border:1px solid #cbd5e0; background:white; cursor:pointer; font-size:12px; box-shadow:0 1px 2px rgba(0,0,0,.08);">Map</button>
                    <button type="button" id="btn-layer-sat" style="padding:6px 10px; border-radius:6px; border:1px solid #cbd5e0; background:white; cursor:pointer; font-size:12px; box-shadow:0 1px 2px rgba(0,0,0,.08);">Satellite</button>
                </div>
                <div id="zone-map-search-wrap" style="position:absolute; z-index:1000; left:50%; transform:translateX(-50%); top:10px; width:92%; max-width:380px;">
                    <input type="text" id="zone_map_search" placeholder="Search location…" autocomplete="off" style="width:100%; box-sizing:border-box; padding:10px 12px; border-radius:8px; border:2px solid #e2e8f0; font-size:14px; box-shadow:0 1px 4px rgba(0,0,0,.12);">
                    <div id="zone_map_search_results" style="display:none; margin-top:4px; max-height:220px; overflow:auto; background:white; border-radius:8px; border:1px solid #e2e8f0; box-shadow:0 4px 12px rgba(0,0,0,.12); font-size:13px;"></div>
                </div>
                <div id="zone-map" style="height:420px; width:100%; border-radius:8px; border:2px solid #e2e8f0;"></div>
            </div>
            <input type="hidden" name="zone[polygon_json]" id="zone_polygon_json" value="{{ $polyStr }}">
        </div>
        <div style="display:flex; gap:16px; flex-wrap:wrap; margin-bottom:14px;">
            <div>
                <label>Map center lat</label>
                <input type="number" step="any" name="zone[default_map_lat]" id="zone_default_lat" value="{{ old('zone.default_map_lat', $zone->default_map_lat ?? '28.6139') }}" style="width:140px; padding:8px; border:2px solid #e2e8f0; border-radius:8px;">
            </div>
            <div>
                <label>Map center lng</label>
                <input type="number" step="any" name="zone[default_map_lng]" id="zone_default_lng" value="{{ old('zone.default_map_lng', $zone->default_map_lng ?? '77.2090') }}" style="width:140px; padding:8px; border:2px solid #e2e8f0; border-radius:8px;">
            </div>
            <div>
                <label>Currency</label>
                <input type="text" name="zone[currency]" value="{{ old('zone.currency', $zone->currency) }}" maxlength="10" style="width:90px; padding:8px; border:2px solid #e2e8f0; border-radius:8px;">
            </div>
        </div>
        <div style="margin-bottom:14px;">
            <label style="font-weight:600;">Price per day (whole zone) *</label>
            <input type="number" name="zone[price_per_day]" value="{{ old('zone.price_per_day', $zone->price_per_day) }}" step="0.01" min="0" required style="width:100%; max-width:220px; padding:10px; border:2px solid #e2e8f0; border-radius:8px;">
        </div>
        <div style="margin-bottom:14px;">
            <label>Zone notes</label>
            <textarea name="zone[notes]" rows="2" style="width:100%; max-width:560px; padding:10px; border:2px solid #e2e8f0; border-radius:8px;">{{ old('zone.notes', $zone->notes) }}</textarea>
        </div>
        <div style="margin-bottom:20px;">
            <label>Zone status *</label>
            <select name="zone[status]" style="padding:8px;">
                <option value="active" {{ old('zone.status', $zone->status) === 'active' ? 'selected' : '' }}>Active</option>
                <option value="inactive" {{ old('zone.status', $zone->status) === 'inactive' ? 'selected' : '' }}>Inactive</option>
                <option value="pending" {{ old('zone.status', $zone->status) === 'pending' ? 'selected' : '' }}>Pending</option>
            </select>
        </div>

        <h3 style="margin:24px 0 12px; font-size:16px;">Vehicles — price per km</h3>
        <table class="table" id="vehicle-rows" style="max-width:800px;">
            <thead>
                <tr>
                    <th>Vehicle</th>
                    <th>Price / km</th>
                    <th>Min charge</th>
                    <th>Row status</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @php
                    $oldV = old('vehicles');
                    $rows = is_array($oldV) ? $oldV : $zoneTransports->map(fn ($t) => [
                        'id' => $t->id,
                        'vehicle_id' => $t->vehicle_id,
                        'price_per_km' => $t->price_per_km,
                        'min_charge' => $t->min_charge,
                        'status' => $t->status,
                    ])->all();
                @endphp
                @foreach($rows as $idx => $row)
                <tr class="vehicle-row">
                    <td>
                        <input type="hidden" name="vehicles[{{ $idx }}][id]" value="{{ $row['id'] ?? '' }}">
                        <select name="vehicles[{{ $idx }}][vehicle_id]" required style="min-width:180px; padding:8px;">
                            <option value="">—</option>
                            @foreach($vehicles as $v)
                                <option value="{{ $v->id }}" {{ (string)($row['vehicle_id'] ?? '') === (string)$v->id ? 'selected' : '' }}>{{ $v->name }}</option>
                            @endforeach
                        </select>
                    </td>
                    <td><input type="number" name="vehicles[{{ $idx }}][price_per_km]" value="{{ $row['price_per_km'] ?? '' }}" step="0.01" min="0" required style="width:110px; padding:8px;"></td>
                    <td><input type="number" name="vehicles[{{ $idx }}][min_charge]" value="{{ $row['min_charge'] ?? '' }}" step="0.01" min="0" style="width:100px; padding:8px;"></td>
                    <td>
                        <select name="vehicles[{{ $idx }}][status]" style="padding:8px;">
                            <option value="active" {{ ($row['status'] ?? 'active') === 'active' ? 'selected' : '' }}>Active</option>
                            <option value="inactive" {{ ($row['status'] ?? '') === 'inactive' ? 'selected' : '' }}>Inactive</option>
                            <option value="pending" {{ ($row['status'] ?? '') === 'pending' ? 'selected' : '' }}>Pending</option>
                        </select>
                    </td>
                    <td><button type="button" class="btn-remove-row" style="padding:6px 10px; border-radius:6px; background:#fed7d7; border:none; cursor:pointer;">Remove</button></td>
                </tr>
                @endforeach
            </tbody>
        </table>
        <button type="button" id="add-vehicle-row" style="margin:10px 0 20px; padding:8px 14px; background:#667eea; color:white; border:none; border-radius:6px; cursor:pointer;">+ Add vehicle</button>

        <div>
            <button type="submit" style="padding:12px 24px; background:#4299e1; color:white; border:none; border-radius:8px; cursor:pointer; font-weight:600;">Update zone &amp; pricing</button>
        </div>
    </form>
</div>
@endsection

@push('styles')
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" crossorigin="">
<link rel="stylesheet" href="https://unpkg.com/leaflet-draw@1.0.4/dist/leaflet.draw.css" crossorigin="">
@endpush

@push('scripts')
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" crossorigin=""></script>
<script src="https://unpkg.com/leaflet-draw@1.0.4/dist/leaflet.draw.js" crossorigin=""></script>
<script>
(function () {
  var token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
  var lat = parseFloat(document.getElementById('zone_default_lat').value) || 28.6139;
  var lng = parseFloat(document.getElementById('zone_default_lng').value) || 77.209;
  var map = L.map('zone-map').setView([lat, lng], 6);
  var baseRoad = L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { attribution: '&copy; OpenStreetMap' });
  var baseSat = L.tileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}', { attribution: 'Tiles &copy; Esri' });
  var activeBase = baseRoad;
  baseRoad.addTo(map);
  document.getElementById('btn-layer-road').addEventListener('click', function () {
    if (activeBase === baseRoad) return;
    map.removeLayer(activeBase);
    baseRoad.addTo(map);
    activeBase = baseRoad;
  });
  document.getElementById('btn-layer-sat').addEventListener('click', function () {
    if (activeBase === baseSat) return;
    map.removeLayer(activeBase);
    baseSat.addTo(map);
    activeBase = baseSat;
  });

  var drawnItems = new L.FeatureGroup();
  map.addLayer(drawnItems);
  var polyHidden = document.getElementById('zone_polygon_json');
  var taCities = document.getElementById('zone_cities_text');
  document.getElementById('zone_cities_unlock').addEventListener('change', function () {
    taCities.readOnly = !this.checked;
    taCities.style.background = this.checked ? '#fff' : '#f7fafc';
  });

  try {
    if (polyHidden.value) {
      var g = JSON.parse(polyHidden.value);
      if (g && (g.type === 'Polygon' || g.type === 'MultiPolygon')) {
        L.geoJSON({ type: 'Feature', geometry: g, properties: {} }).eachLayer(function (layer) { drawnItems.addLayer(layer); });
        map.fitBounds(drawnItems.getBounds(), { padding: [24, 24] });
      }
    }
  } catch (e) {}
  map.addControl(new L.Control.Draw({
    draw: { polygon: { allowIntersection: false, shapeOptions: { color: '#667eea', weight: 2 } }, polyline: false, rectangle: false, circle: false, marker: false, circlemarker: false },
    edit: { featureGroup: drawnItems, remove: true }
  }));
  map.on(L.Draw.Event.CREATED, function (e) {
    drawnItems.clearLayers();
    drawnItems.addLayer(e.layer);
    polyHidden.value = JSON.stringify(e.layer.toGeoJSON().geometry);
    var c = e.layer.getBounds().getCenter();
    document.getElementById('zone_default_lat').value = c.lat.toFixed(6);
    document.getElementById('zone_default_lng').value = c.lng.toFixed(6);
  });
  map.on(L.Draw.Event.EDITED, function (e) {
    e.layers.eachLayer(function (layer) {
      polyHidden.value = JSON.stringify(layer.toGeoJSON().geometry);
    });
  });
  map.on(L.Draw.Event.DELETED, function () { polyHidden.value = ''; });

  function mapCenter() {
    if (drawnItems.getLayers().length) {
      var c = drawnItems.getBounds().getCenter();
      return { lat: c.lat, lng: c.lng };
    }
    return { lat: map.getCenter().lat, lng: map.getCenter().lng };
  }

  var searchBox = document.getElementById('zone_map_search');
  var searchRes = document.getElementById('zone_map_search_results');
  var searchTimer = null;
  searchBox.addEventListener('input', function () {
    var q = searchBox.value.trim();
    clearTimeout(searchTimer);
    searchRes.style.display = 'none';
    searchRes.innerHTML = '';
    if (q.length < 2) return;
    searchTimer = setTimeout(function () {
      fetch('{{ route('admin.transports.forward-geocode') }}', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': token, 'Accept': 'application/json' },
        body: JSON.stringify({ q: q })
      }).then(function (r) { return r.json(); }).then(function (d) {
        if (!d.success || !d.results || !d.results.length) { return; }
        searchRes.innerHTML = d.results.map(function (x, i) {
          return '<div class="zone-search-hit" data-i="' + i + '" style="padding:10px 12px; cursor:pointer; border-bottom:1px solid #edf2f7;">' + String(x.label).replace(/</g, '') + '</div>';
        }).join('');
        searchRes._data = d.results;
        searchRes.style.display = 'block';
        [].forEach.call(searchRes.querySelectorAll('.zone-search-hit'), function (el) {
          el.addEventListener('mousedown', function (ev) {
            ev.preventDefault();
            var x = searchRes._data[parseInt(el.getAttribute('data-i'), 10)];
            if (!x) return;
            map.setView([x.lat, x.lng], Math.max(map.getZoom(), 13));
            document.getElementById('zone_default_lat').value = x.lat.toFixed(6);
            document.getElementById('zone_default_lng').value = x.lng.toFixed(6);
            searchRes.style.display = 'none';
            searchBox.value = '';
          });
        });
      }).catch(function () {});
    }, 400);
  });
  document.addEventListener('click', function (e) {
    if (!document.getElementById('zone-map-search-wrap').contains(e.target)) searchRes.style.display = 'none';
  });

  document.getElementById('btn-fill-cities-polygon').addEventListener('click', function () {
    if (!polyHidden.value) { alert('Draw a zone polygon first.'); return; }
    var btn = this;
    btn.disabled = true;
    fetch('{{ route('admin.transports.suggest-zone-cities') }}', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': token, 'Accept': 'application/json' },
      body: JSON.stringify({ polygon_json: polyHidden.value })
    }).then(function (r) { return r.json(); }).then(function (d) {
      btn.disabled = false;
      if (!d.success) { alert(d.message || 'Could not get place names'); return; }
      if (!d.cities || !d.cities.length) { alert('No city names returned.'); return; }
      taCities.value = d.cities.join('\n');
    }).catch(function () { btn.disabled = false; alert('Request failed'); });
  });

  document.getElementById('btn-suggest-city').addEventListener('click', function () {
    var c = mapCenter();
    fetch('{{ route('admin.transports.reverse-geocode') }}', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': token, 'Accept': 'application/json' },
      body: JSON.stringify({ lat: c.lat, lng: c.lng })
    }).then(function (r) { return r.json(); }).then(function (d) {
      if (!d.success || !d.line) { alert(d.message || 'No suggestion'); return; }
      var cur = taCities.value.trim();
      taCities.value = cur ? (cur + '\n' + d.line) : d.line;
    }).catch(function () { alert('Geocode request failed'); });
  });

  var rowIdx = {{ count($rows) }};
  var vehiclesHtml = @json($vehicles->map(fn($v) => ['id' => $v->id, 'name' => $v->name])->values());
  document.getElementById('add-vehicle-row').addEventListener('click', function () {
    var opts = vehiclesHtml.map(function (v) { return '<option value="' + v.id + '">' + String(v.name).replace(/</g,'') + '</option>'; }).join('');
    var tr = document.createElement('tr');
    tr.className = 'vehicle-row';
    tr.innerHTML = '<td><input type="hidden" name="vehicles[' + rowIdx + '][id]" value="">' +
      '<select name="vehicles[' + rowIdx + '][vehicle_id]" required style="min-width:180px;padding:8px;"><option value="">—</option>' + opts + '</select></td>' +
      '<td><input type="number" name="vehicles[' + rowIdx + '][price_per_km]" step="0.01" min="0" required style="width:110px;padding:8px;"></td>' +
      '<td><input type="number" name="vehicles[' + rowIdx + '][min_charge]" step="0.01" min="0" style="width:100px;padding:8px;"></td>' +
      '<td><select name="vehicles[' + rowIdx + '][status]" style="padding:8px;"><option value="active">Active</option><option value="inactive">Inactive</option><option value="pending">Pending</option></select></td>' +
      '<td><button type="button" class="btn-remove-row" style="padding:6px 10px;border-radius:6px;background:#fed7d7;border:none;cursor:pointer;">Remove</button></td>';
    document.querySelector('#vehicle-rows tbody').appendChild(tr);
    rowIdx++;
  });
  document.getElementById('zone-bundle-form').addEventListener('click', function (e) {
    if (e.target.classList.contains('btn-remove-row')) {
      var rows = document.querySelectorAll('#vehicle-rows tbody .vehicle-row');
      if (rows.length <= 1) { alert('Keep at least one vehicle row.'); return; }
      e.target.closest('tr').remove();
    }
  });
})();
</script>
@endpush
