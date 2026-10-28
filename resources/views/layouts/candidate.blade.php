<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>@yield('title', 'Exam') · UATH Examination System</title>
<style>
:root{--bg:#f4f6fb;--card:#fff;--text:#1b2433;--muted:#5d6b82;--border:#d9dfeb;--accent:#2f5496;--accent-t:#fff;--bad:#c0392b;--bad-bg:#fdecea;color-scheme:light}
@media (prefers-color-scheme:dark){:root{--bg:#0f141d;--card:#18202d;--text:#e8edf5;--muted:#9aa8bf;--border:#2b3648;--accent:#6c97e6;--accent-t:#0f141d;--bad:#ef6b5b;--bad-bg:#2c1a1a;color-scheme:dark}}
*{box-sizing:border-box}
body{margin:0;background:var(--bg);color:var(--text);font:16px/1.5 system-ui,-apple-system,"Segoe UI",Roboto,Arial,sans-serif}
.wrap{max-width:440px;margin:0 auto;padding:32px 16px 48px}
.brand{font-weight:700;color:var(--accent);text-align:center;margin-bottom:18px}
.card{background:var(--card);border:1px solid var(--border);border-radius:14px;padding:24px}
h1{font-size:22px;margin:0 0 6px}
.muted,.note{color:var(--muted);font-size:14px}
label{display:block;font-weight:600;margin:16px 0 6px}
input[type=text],input[type=password]{width:100%;font:inherit;color:inherit;background:var(--card);border:1.5px solid var(--border);border-radius:10px;padding:12px 14px}
input:focus{outline:2px solid var(--accent);outline-offset:1px;border-color:var(--accent)}
.pin-row{display:flex;gap:8px}
.pin-row input{letter-spacing:.3em;font-size:20px}
.btn{width:100%;margin-top:20px;font:inherit;font-weight:600;background:var(--accent);color:var(--accent-t);border:0;border-radius:10px;padding:13px 22px;cursor:pointer}
.btn:disabled{opacity:.6;cursor:default}
.ghost{font:inherit;font-weight:600;background:transparent;color:var(--accent);border:1.5px solid var(--accent);border-radius:10px;padding:0 14px;cursor:pointer}
.alert{background:var(--bad-bg);color:var(--bad);border:1px solid var(--bad);border-radius:10px;padding:10px 14px;margin:14px 0 0;font-size:15px}
.row{display:flex;justify-content:space-between;gap:12px;padding:9px 0;border-bottom:1px solid var(--border)}
.row:last-of-type{border-bottom:0}
</style>
</head>
<body>
<div class="wrap">
  <div class="brand">UATH Examination System</div>
  @yield('content')
</div>
</body>
</html>
