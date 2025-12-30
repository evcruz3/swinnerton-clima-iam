<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Keycloak Login Example</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .container {
            background: white;
            padding: 40px;
            border-radius: 10px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
            max-width: 400px;
            width: 100%;
            text-align: center;
        }
        h1 {
            color: #333;
            margin-bottom: 10px;
            font-size: 28px;
        }
        p {
            color: #666;
            margin-bottom: 30px;
            line-height: 1.6;
        }
        .btn {
            display: inline-block;
            background: #667eea;
            color: white;
            padding: 15px 40px;
            text-decoration: none;
            border-radius: 5px;
            font-weight: 600;
            transition: all 0.3s ease;
            border: none;
            cursor: pointer;
            font-size: 16px;
        }
        .btn:hover {
            background: #5568d3;
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(102, 126, 234, 0.4);
        }
        .info {
            margin-top: 30px;
            padding-top: 30px;
            border-top: 1px solid #eee;
        }
        .info-item {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
            font-size: 14px;
        }
        .info-label {
            color: #666;
            font-weight: 600;
        }
        .info-value {
            color: #333;
            font-family: monospace;
        }
        .error {
            background: #fee;
            color: #c33;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
            border: 1px solid #fcc;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Welcome to CLIMA</h1>
        <p>Please login with your Keycloak account to continue</p>

        <?php if ($this->session->flashdata('error')): ?>
            <div class="error">
                <?= htmlspecialchars($this->session->flashdata('error'), ENT_QUOTES, 'UTF-8') ?>
            </div>
        <?php endif; ?>

        <a href="<?= base_url('auth/login') ?>" class="btn">Login with Keycloak</a>

        <div class="info">
            <div class="info-item">
                <span class="info-label">Realm:</span>
                <span class="info-value">CLIMA</span>
            </div>
            <div class="info-item">
                <span class="info-label">Client:</span>
                <span class="info-value">clima-frontend</span>
            </div>
            <div class="info-item">
                <span class="info-label">SSO Server:</span>
                <span class="info-value">devsso.swinnertonsolutions.com</span>
            </div>
        </div>
    </div>
</body>
</html>
