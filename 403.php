<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <title>Accès interdit</title>
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <style>
    body {
      background: #1a1a1a;
      color: #fff;
      font-family: Arial, sans-serif;
      display: flex;
      justify-content: center;
      align-items: center;
      height: 100vh;
    }
  </style>
</head>
<body>
<script>
  Swal.fire({
    icon: 'error',
    title: '⛔ Accès refusé',
    text: 'Tu n\'as pas la permission d\'accéder à cette page.',
    footer: '<a href="/">Retour à l\'accueil</a>'
  });
</script>
</body>
</html>
