# Messagerie - Template PHP/MySQL

<img width="960" alt="messagerie-by-berru-g" src="https://github.com/user-attachments/assets/58183418-1434-42e3-83c5-06dcd187fdda" />


<img width="960" alt="messagerie-connection-by-berru-g png" src="https://github.com/user-attachments/assets/e61b9d22-b33c-44c7-9200-f75e66b2526f" />

Système de messagerie avec authentification, posts et likes, partage de fichier ( csv, google sheet ou json ) 100% personnalisable.

## ✨ Fonctionnalités

- ✅ **Authentification sécurisée** (inscription/connexion)
- 📝 **Création de posts** avec markdown de base
- ❤️ **Système de likes** interactif
- 🔒 **Gestion de profil** (changement mot de passe)
- 🗂️ **Partage de fichier** (.csv .json ou excel)
- 📱 **Design responsive** et moderne
- 🎨 **Personnalisation facile** via variables CSS

## 🚀 Installation

### Prérequis
- PHP 7.4+
- MySQL 5.7+
- Serveur web (Apache/Nginx)

### Étapes
1. **Cloner le dépôt** :
   ```bash
   git clone https://github.com/berru-g/messagerie-collegues.git

2. **Configurer les différentes tables dans la base de données :**
 - [full config sql](https://github.com/berru-g/projet-messagerie/blob/main/includes/config.sql)


Configurer les variables :
bash

cp includes/config.dist.php includes/config.php

Modifiez config.php avec vos identifiants DB.

Lancer le serveur :
bash

    php -S localhost:8000


🔧 Troubleshooting

Problème : Erreur SQL avec emojis
Solution : Exécuter :
sql

ALTER DATABASE messagerie_collegues 
CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

Problème : Page blanche
Solution : Activer les logs dans config.php :
php

ini_set('display_errors', 1);
error_reporting(E_ALL);


MIT License - Libre d'utilisation et modification

Les PR sont les bienvenues ! Ouvrez une issue pour discuter des améliorations.

Développé avec ❤️ par berru-g et chatgpt
