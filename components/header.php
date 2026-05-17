<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Deal Detective</title>

    <script src="https://cdn.tailwindcss.com"></script>

    <style>
        /* DEFAULT DARK MODE */
        body{
            background: #0f172a;
            color: white;
            transition: all 0.3s ease;
        }

        /* LIGHT MODE */
        .light-mode{
            filter: invert(1) hue-rotate(180deg);
            background: white;
        }

        /* FIX IMAGES */
        .light-mode img{
            filter: invert(1) hue-rotate(180deg);
        }

        .glass{
            background: rgba(255,255,255,0.05);
            backdrop-filter: blur(12px);
            border: 1px solid rgba(255,255,255,0.08);
        }
        *{
            scroll-behavior: smooth;
        }

        

        .float{
            animation: float 4s ease-in-out infinite;
        }

        .glow:hover{
            box-shadow: 0 0 30px rgba(34,211,238,0.35);
        }

        .card-hover{
            transition: all 0.35s ease;
        }

        .card-hover:hover{
            transform: translateY(-8px) scale(1.02);
        }

        .gradient-text{
        background: linear-gradient(to right, #22d3ee, #3b82f6);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        }
        .animate-toast{
            animation: toastSlide 0.4s ease;
        }

        .animate-fade{
            animation: fadeIn 0.2s ease;
        }
       .card-enter{
            opacity: 0;
            transform: translateY(30px);
            animation: cardEnter 0.8s ease forwards;
        }

        @keyframes cardEnter{

            to{
                opacity: 1;
                transform: translateY(0);
            }
        }  
        @keyframes toastSlide{
            from{
                opacity:0;
                transform: translateX(100%);
            }
            to{
                opacity:1;
                transform: translateX(0);
            }
        }

        @keyframes fadeIn{
            from{
                opacity:0;
                transform: translateY(20px);
            }
            to{
                opacity:1;
                transform: translateY(0);
            }
        }

        @keyframes float{
            0%{
                transform: translateY(0px);
            }
            50%{
                transform: translateY(-10px);
            }
            100%{
                transform: translateY(0px);
            }
        }
            </style>
</head>

<body class="min-h-screen">

<?php include(__DIR__ . "/navbar.php"); ?>