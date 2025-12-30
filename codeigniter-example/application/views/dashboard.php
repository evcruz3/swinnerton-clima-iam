<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - CLIMA</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            background: #f5f5f5;
            min-height: 100vh;
        }
        .header {
            background: white;
            padding: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .header h1 {
            color: #333;
            font-size: 24px;
        }
        .container {
            max-width: 1200px;
            margin: 40px auto;
            padding: 0 20px;
        }
        .card {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        .card h2 {
            color: #333;
            margin-bottom: 20px;
            font-size: 20px;
        }
        .user-info {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
        }
        .info-item {
            padding: 15px;
            background: #f9f9f9;
            border-radius: 5px;
            border-left: 4px solid #667eea;
        }
        .info-label {
            color: #666;
            font-size: 12px;
            text-transform: uppercase;
            font-weight: 600;
            margin-bottom: 5px;
        }
        .info-value {
            color: #333;
            font-size: 16px;
            word-break: break-all;
        }
        .btn {
            display: inline-block;
            background: #667eea;
            color: white;
            padding: 12px 30px;
            text-decoration: none;
            border-radius: 5px;
            font-weight: 600;
            transition: all 0.3s ease;
            border: none;
            cursor: pointer;
            font-size: 14px;
        }
        .btn:hover {
            background: #5568d3;
        }
        .btn-danger {
            background: #e74c3c;
        }
        .btn-danger:hover {
            background: #c0392b;
        }
        .welcome {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 40px;
            border-radius: 10px;
            margin-bottom: 20px;
        }
        .welcome h2 {
            color: white;
            margin-bottom: 10px;
        }
        .json-viewer {
            background: #2d2d2d;
            color: #f8f8f2;
            padding: 20px;
            border-radius: 5px;
            overflow-x: auto;
            font-family: 'Courier New', monospace;
            font-size: 14px;
            line-height: 1.6;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>CLIMA Dashboard</h1>
        <a href="<?= base_url('auth/logout') ?>" class="btn btn-danger">Logout</a>
    </div>

    <div class="container">
        <div class="welcome">
            <h2>Welcome, <?= htmlspecialchars(isset($user->name) ? $user->name : (isset($user->preferred_username) ? $user->preferred_username : 'User'), ENT_QUOTES, 'UTF-8') ?>!</h2>
            <p>You have successfully logged in via Keycloak SSO</p>
        </div>

        <div class="card">
            <h2>User Information</h2>
            <div class="user-info">
                <?php if (isset($user->preferred_username)): ?>
                <div class="info-item">
                    <div class="info-label">Username</div>
                    <div class="info-value"><?= htmlspecialchars($user->preferred_username, ENT_QUOTES, 'UTF-8') ?></div>
                </div>
                <?php endif; ?>

                <?php if (isset($user->name)): ?>
                <div class="info-item">
                    <div class="info-label">Full Name</div>
                    <div class="info-value"><?= htmlspecialchars($user->name, ENT_QUOTES, 'UTF-8') ?></div>
                </div>
                <?php endif; ?>

                <?php if (isset($user->email)): ?>
                <div class="info-item">
                    <div class="info-label">Email</div>
                    <div class="info-value"><?= htmlspecialchars($user->email, ENT_QUOTES, 'UTF-8') ?></div>
                </div>
                <?php endif; ?>

                <?php if (isset($user->email_verified)): ?>
                <div class="info-item">
                    <div class="info-label">Email Verified</div>
                    <div class="info-value"><?= $user->email_verified ? 'Yes' : 'No' ?></div>
                </div>
                <?php endif; ?>

                <?php if (isset($user->sub)): ?>
                <div class="info-item">
                    <div class="info-label">User ID (sub)</div>
                    <div class="info-value"><?= htmlspecialchars($user->sub, ENT_QUOTES, 'UTF-8') ?></div>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="card">
            <h2>Raw User Data (JSON)</h2>
            <div class="json-viewer">
                <pre><?= json_encode($user, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) ?></pre>
            </div>
        </div>
    </div>
</body>
</html>
