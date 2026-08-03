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
    .iso-field-wide { grid-column:1 / -1; }
    .iso-decision-reason { padding:14px 16px; background:#fff8e6; border:1px solid #f0d999; border-left:4px solid #e5b94f; border-radius:9px; }
    .iso-decision-reason label { color:#795b00; }
    .iso-decision-reason small { display:block; margin-top:7px; color:#667085; }
    .iso-tabs { border-bottom:1px solid #dfe4ea; gap:4px; }
    .iso-tabs .nav-link { color:#667085!important; background:#f8fafc; border:1px solid transparent; border-radius:10px 10px 0 0; font-weight:700; padding:12px 18px; }
    .iso-tabs .nav-link:hover { color:#2457e6!important; border-color:#e3e7ed #e3e7ed #dfe4ea; }
    .iso-tabs .nav-link.active { color:#17365d!important; background:#fff; border-color:#dfe4ea #dfe4ea #fff; }
    .iso-action-card { background:linear-gradient(135deg,#f8fafc 0%,#f4f7fb 100%); border:1px solid #d9e1ea; border-left:4px solid #b8c8df; border-radius:10px; padding:18px; margin-bottom:18px; box-shadow:0 3px 10px rgba(16,24,40,.035); }
    .iso-action-card:hover { border-color:#c4d0df; border-left-color:#7f9fc8; }
    .iso-action-summary { width:100%; border:0; background:transparent; padding:0; display:flex; justify-content:space-between; align-items:center; gap:18px; text-align:left; color:#172b4d; }
    .iso-action-summary strong { display:block; font-size:1rem; }
    .iso-action-summary small { display:block; color:#667085; margin-top:5px; font-weight:400; }
    .iso-action-summary:focus { outline:2px solid rgba(36,87,230,.18); outline-offset:6px; border-radius:4px; }
    .iso-action-chevron { color:#667085; transition:transform .2s ease; }
    .iso-action-summary[aria-expanded="true"] .iso-action-chevron { transform:rotate(180deg); }
    .iso-action-detail { border-top:1px solid #d9e1ea; margin-top:16px; padding-top:14px; }
    .iso-section-card { background:#f3f6fa; border-color:#cdd8e5; box-shadow:0 9px 24px rgba(16,24,40,.09); overflow:hidden; }
    .iso-section-card .iso-panel-header { background:#eaf0f6; border-bottom-color:#cdd8e5; }
    .iso-section-card > .iso-panel-body, .iso-section-card > form.iso-panel-body { background:#f6f8fb; }
    .iso-evaluation-form { width:100%; max-width:none; }
    .iso-evaluation-card { background:linear-gradient(135deg,#f3f6fa 0%,#edf2f7 100%); border:1px solid #cfd9e5; border-left:5px solid #9fb2ca; border-radius:10px; padding:17px 19px; margin-bottom:18px; box-shadow:0 5px 14px rgba(16,24,40,.07); }
    .iso-evaluation-card.is-effective { border-left-color:#86c89a; }
    .iso-evaluation-card.is-partial { border-left-color:#e5c467; }
    .iso-evaluation-card.is-ineffective { border-left-color:#df9292; }
    .iso-empty-card { display:flex; align-items:center; background:#e9eff6; border:1px dashed #b8c6d6; border-radius:10px; padding:18px; color:#52657c; }
    .iso-empty-card i { font-size:1.25rem; color:#7e93ad; }
    .iso-empty-card strong,.iso-empty-card small { display:block; }
    .iso-empty-card small { margin-top:3px; color:#667085; }
    .iso-wizard { overflow:hidden; }
    .iso-wizard-steps { display:flex; align-items:center; padding:22px 24px; background:#f8fafc; border-bottom:1px solid #dfe4ea; }
    .iso-wizard-step { display:flex; align-items:center; gap:10px; padding:0; border:0; background:transparent; color:#667085; text-align:left; }
    .iso-wizard-step > span { width:34px; height:34px; flex:0 0 34px; display:grid; place-items:center; border:2px solid #cbd5e1; border-radius:50%; background:#fff; font-weight:800; }
    .iso-wizard-step strong,.iso-wizard-step small { display:block; white-space:nowrap; }
    .iso-wizard-step strong { color:#475467; font-size:.9rem; }
    .iso-wizard-step small { font-size:.73rem; margin-top:2px; }
    .iso-wizard-step.active > span { color:#fff; border-color:#2457e6; background:#2457e6; box-shadow:0 0 0 4px rgba(36,87,230,.12); }
    .iso-wizard-step.active strong { color:#17365d; }
    .iso-wizard-step.complete > span { color:#166534; border-color:#86c89a; background:#dcfce7; }
    .iso-wizard-line { height:2px; flex:1; min-width:30px; margin:0 18px; background:#d8e0ea; }
    .iso-wizard-heading { display:flex; align-items:center; gap:12px; margin-bottom:22px; }
    .iso-wizard-heading > span { width:38px; height:38px; display:grid; place-items:center; border-radius:10px; color:#2457e6; background:#eef4ff; font-weight:800; }
    .iso-wizard-heading h2 { margin:0; color:#17365d; font-size:1.15rem; font-weight:800; }
    .iso-wizard-heading p { margin:3px 0 0; color:#667085; }
    .iso-wizard-footer { display:flex; justify-content:space-between; align-items:center; gap:16px; padding:16px 18px; background:#f8fafc; border-top:1px solid #dfe4ea; }
    .iso-wizard-counter { color:#667085; font-size:.85rem; }
    .iso-optional-note { display:flex; gap:12px; align-items:center; margin-bottom:20px; padding:14px 16px; color:#52657c; background:#eef4ff; border:1px solid #d5e2f7; border-radius:9px; }
    .iso-optional-note i { color:#2457e6; font-size:1.25rem; }
    .iso-optional-note strong,.iso-optional-note span { display:block; }
    .iso-optional-note span { margin-top:2px; font-size:.86rem; }
    @media(max-width: 992px){ .iso-card-grid{grid-template-columns:1fr 1fr}.iso-kpi-row{grid-template-columns:1fr 1fr}.iso-foda-grid,.iso-detail-grid{grid-template-columns:1fr} }
    @media(max-width: 600px){ .iso-card-grid{grid-template-columns:1fr}.iso-shell{padding:18px 12px}.iso-kpi-row{grid-template-columns:1fr 1fr}.iso-wizard-steps{padding:16px}.iso-wizard-step div{display:none}.iso-wizard-line{margin:0 8px}.iso-wizard-footer{align-items:stretch;flex-wrap:wrap}.iso-wizard-counter{order:-1;width:100%;text-align:center}.iso-wizard-footer>div{margin-left:auto} }
    @media print { nav,.btn,.iso-toolbar,.no-print{display:none!important}.iso-shell{max-width:none;padding:0}.iso-panel{box-shadow:none} }
</style>
@endpush
@endonce
