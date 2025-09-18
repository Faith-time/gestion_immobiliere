<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Agence Immobilieree</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <meta name="csrf-token" content="{{ csrf_token() }}">


    {{-- Inertia + Vite --}}
    @vite('resources/js/app.js')
    @routes
</head>
<body>
@inertia

</body>
</html>
