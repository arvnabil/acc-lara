import { useEffect, useState } from 'react';

export default function AddressLocationPicker({ value, onLocationChange }) {
  const [query, setQuery] = useState(value.subdistrictName || value.city || '');
  const [areas, setAreas] = useState([]);
  const [loading, setLoading] = useState(false);
  const [selectedAreaId, setSelectedAreaId] = useState(value.biteshipAreaId || null);

  useEffect(() => {
    if (selectedAreaId) {
      return;
    }

    if (query.trim().length < 3) {
      setAreas([]);
      return;
    }

    const controller = new AbortController();
    const timer = setTimeout(() => {
      setLoading(true);
      fetch(`/shipping/biteship/areas?input=${encodeURIComponent(query.trim())}`, { headers: { Accept: 'application/json' }, signal: controller.signal })
        .then(response => response.ok ? response.json() : Promise.reject(new Error('Gagal memuat lokasi.')))
        .then(payload => setAreas(payload.areas || []))
        .catch(error => { if (error.name !== 'AbortError') setAreas([]); })
        .finally(() => setLoading(false));
    }, 700);

    return () => { clearTimeout(timer); controller.abort(); };
  }, [query, selectedAreaId]);

  const choose = area => {
    setQuery(area.name);
    setAreas([]);
    setSelectedAreaId(area.id);
    onLocationChange({
      province: area.province,
      provinceName: area.province,
      city: area.city,
      cityName: area.city,
      districtName: area.district,
      subdistrictName: area.name.split(',')[0] || area.district,
      postalCode: area.postal_code,
      biteshipAreaId: area.id,
    });
  };

  const fieldClass = 'w-full rounded-lg border border-border-subtle bg-surface px-3.5 py-2 text-xs text-text-primary focus:border-primary focus:outline-none sm:text-sm';
  return (
    <div className="mb-4 rounded-lg border border-primary/20 bg-primary/5 p-3">
      <p className="mb-2 text-xs font-semibold text-text-primary">Pilih lokasi Biteship</p>
      <div className="relative">
        <input value={query} onChange={event => { setSelectedAreaId(null); setQuery(event.target.value); }} placeholder="Ketik kecamatan, kota, atau kode pos..." className={`${fieldClass} mt-1`} />
        {loading && <p className="mt-1 text-[11px] text-text-muted">Mencari lokasi...</p>}
        {areas.length > 0 && <div className="absolute z-20 mt-1 max-h-52 w-full overflow-auto rounded-lg border border-border-subtle bg-surface shadow-lg">{areas.map(area => <button type="button" key={area.id} onClick={() => choose(area)} className="block w-full border-b border-border-subtle px-3 py-2 text-left text-xs hover:bg-primary/10">{area.name}</button>)}</div>}
      </div>
      <p className="mt-2 text-[11px] text-text-muted">Lokasi, kecamatan, dan kode pos diisi dari Biteship Maps.</p>
    </div>
  );
}
