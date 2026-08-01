@once
@push('styles')
<style>
    .iso-shell { max-width: 1450px; margin: 0 auto; padding: 24px 22px 40px; }
    .iso-eyebrow { color: #2457e6; font-size: .75rem; font-weight: 800; letter-spacing: .11em; text-transform: uppercase; }
    .iso-title { color: #111827; font-size: 2rem; font-weight: 800; margin: 3px 0 4px; }
    .iso-subtitle { color: #667085; max-width: 850px; }
    .iso-card-grid { display: grid; grid-template-columns: repeat(3, minmax(0,1fr)); gap: 18px; margin-top: 26px; }
    .iso-menu-card { background: #fff; border: 1px solid #dfe4ea; border-radius: 14px; min-height: 180px; padding: 24px; display: flex; gap: 18px; color: inherit; text-decoration: none!important; box-shadow: 0 8px 22px rgba(16,24,40,.05); transition: transform .15s, box-shadow .15s; }
    .iso-menu-card:hover { color: inherit; transform: translateY(-2px); box-shadow: 0 12px 28px rgba(16,24,40,.09); }
    .iso-icon { width: 56px; height: 56px; flex: 0 0 56px; border-radius: 14px; background: #eef4ff; color: #2457e6; display: grid; place-items: center; font-size: 1.35rem; }
    .iso-menu-card h3 { font-size: 1.05rem; font-weight: 800; margin: 4px 0 8px; }
    .iso-menu-card p { color: #667085; line-height: 1.55; margin: 0; }
    .iso-badge-count { display: inline-block; margin-top: 14px; padding: 4px 9px; border-radius: 999px; background: #fff3cd; color: #795b00; font-size: .78rem; font-weight: 700; }
    .iso-toolbar { display:flex; justify-content:space-between; align-items:center; gap:12px; margin:20px 0; flex-wrap:wrap; }
    .iso-panel { background:#fff; border:1px solid #dfe4ea; border-radius:12px; box-shadow:0 6px 18px rgba(16,24,40,.04); }
    .iso-panel-header { padding:16px 18px; border-bottom:1px solid #e8ecf1; display:flex; justify-content:space-between; align-items:center; gap:10px; }
    .iso-panel-body { padding:18px; }
    .iso-kpi-row { display:grid; grid-template-columns:repeat(4,minmax(0,1fr)); gap:14px; margin-top:20px; }
    .iso-kpi { background:#fff; border:1px solid #e1e6ec; border-radius:10px; padding:16px; }
    .iso-kpi strong { display:block; font-size:1.75rem; color:#17365d; }
    .iso-kpi span { color:#667085; font-size:.86rem; }
    .iso-foda-grid { display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); gap:16px; }
    .iso-foda-column { border-radius:12px; padding:16px; background:#f8fafc; border:1px solid #e2e8f0; }
    .iso-foda-item { display:block; background:#fff; color:inherit!important; text-decoration:none!important; border:1px solid #e3e7ed; border-radius:9px; padding:13px; margin-top:10px; }
    .iso-foda-item:hover { border-color:#7294f4; }
    .iso-code { font-family:ui-monospace, SFMono-Regular, Menlo, monospace; color:#2457e6; font-size:.78rem; font-weight:700; }
    .iso-status { display:inline-block; border-radius:999px; padding:3px 8px; font-size:.75rem; font-weight:700; background:#eef2f6; color:#475467; }
    .iso-status.ok { background:#dcfce7; color:#166534; } .iso-status.warn { background:#fff3cd; color:#795b00; } .iso-status.danger { background:#fee2e2; color:#991b1b; }
    .iso-table th { background:#f8fafc; color:#344054; border-top:0; font-size:.8rem; } .iso-table td { vertical-align:middle; font-size:.88rem; }
    .iso-section-title { font-size:1.05rem; font-weight:800; color:#17365d; }
    .iso-detail-grid { display:grid; grid-template-columns:1fr 1fr; gap:16px; }
    .iso-field label { display:block; color:#667085; font-size:.76rem; font-weight:700; text-transform:uppercase; margin-bottom:3px; }
    .iso-field div { white-space:pre-line; }
    @media(max-width: 992px){ .iso-card-grid{grid-template-columns:1fr 1fr}.iso-kpi-row{grid-template-columns:1fr 1fr}.iso-foda-grid,.iso-detail-grid{grid-template-columns:1fr} }
    @media(max-width: 600px){ .iso-card-grid{grid-template-columns:1fr}.iso-shell{padding:18px 12px}.iso-kpi-row{grid-template-columns:1fr 1fr} }
    @media print { nav,.btn,.iso-toolbar,.no-print{display:none!important}.iso-shell{max-width:none;padding:0}.iso-panel{box-shadow:none} }
</style>
@endpush
@endonce
