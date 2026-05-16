<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>

    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>آریا جانبی CRM</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        body {
            background: linear-gradient(135deg, #f8fafc, #e2e8f0);
            font-family: 'IRANSans', sans-serif;
        }

        .container {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 20px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.1);
            padding: 60px 40px;
            text-align: center;
            margin: 0 auto;
        }

        .logo {
            font-size: 3.2rem;
            color: #e53e3e;
            font-weight: bold;
            margin-bottom: 0.5rem;
        }

        .slogan {
            font-size: 1.4rem;
            color: #4a5568;
            margin-bottom: 2.5rem;
        }

        .button {
            padding: 14px 32px;
            border-radius: 10px;
            font-size: 1rem;
            font-weight: 600;
            transition: background-color 0.3s, transform 0.2s;
            text-decoration: none;
        }

        .button:hover {
            transform: translateY(-2px);
        }

        .button-primary {
            background-color: #e53e3e;
            color: white;
        }

        .button-primary:hover {
            background-color: #c53030;
        }

        .button-secondary {
            background-color: #edf2f7;
            color: #2d3748;
        }

        .button-secondary:hover {
            background-color: #e2e8f0;
        }

        .button-group {
            display: flex;
            flex-direction: column;
            gap: 15px;
            align-items: center;
        }

        @media (min-width: 640px) {
            .button-group {
                flex-direction: row;
                justify-content: center;
            }
        }

        .footer {
            margin-top: 40px;
            color: #a0aec0;
            font-size: 0.9rem;
        }

        @media (min-width: 768px) {
            .container {
                max-width: 700px;
            }
        }
    </style>
</head>
<body class="font-sans antialiased">

<div class="min-h-screen flex flex-col items-center justify-center px-4">
    <div class="container w-full">
        <div class="" style="
        display: flex;
        justify-content: center; /* افقی */
        /* align-items: center; */     /* عمودی */
        height: 200px;
    ">
<img src="{{ asset('logo.png') }}" alt="Logo">

            </div>

        <p class="slogan">
            آریا جانبی
        </p>

        <div class="button-group">
            @auth
                <a href="{{ route('dashboard') }}" class="button button-primary">
                    ورود به داشبورد
                </a>
            @else
                <a href="{{ route('login') }}" class="button button-primary">
                    ورود به CRM
                </a>
               
                    <a href="http://192.168.1.30:8080/" class="button button-primary">
                        ورود به سیستم انبار
                    </a>
               
            @endauth
        </div>

        <div class="footer mt-12">
            © {{ now()->year }} آریا جانبی — تمامی حقوق محفوظ است.
        </div>
    </div>
</div>

</body>
</html>
