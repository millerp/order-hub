<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $service }} — OrderHub</title>
    <style>
        body { font-family: system-ui, sans-serif; display: flex; justify-content: center; align-items: center; min-height: 100vh; margin: 0; background: #f5f5f5; }
        .card { background: #fff; padding: 2rem 3rem; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,.08); text-align: center; }
        h1 { margin: 0 0 0.5rem; font-size: 1.5rem; color: #1b1b18; }
        .badge { display: inline-block; background: #1b1b18; color: #fff; padding: 0.25rem 0.75rem; border-radius: 4px; font-weight: 600; }
        p { margin: 0; color: #666; font-size: 0.9rem; }
    </style>
</head>
<body>
    <div class="card">
        <p>Você está acessando</p>
        <h1><span class="badge">{{ $service }}</span></h1>
    </div>
</body>
</html>
