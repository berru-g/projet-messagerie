# Template PHP/MySQL 
Système de messagerie avec authentification, posts et likes, partage de fichier ( csv, google sheet ou json ). 
**Objectif:** *mise à jour auto des prix d'une fiche technique puisquee pas d'api, demander aux fournisseurs d'upload la maj, les pro récupère leurs fiches à jour dans le format demandé.*
 - 07/25 : V1.1 Développé par berru-g et deepseek

### Login/register/changepassword

<img width="960" alt="messagerie-connection-by-berru-g png" src="https://github.com/user-attachments/assets/e61b9d22-b33c-44c7-9200-f75e66b2526f" />


### Messagerie/like

<img width="960" alt="messagerie-by-berru-g" src="https://github.com/user-attachments/assets/58183418-1434-42e3-83c5-06dcd187fdda" />


### Upload csv, excel, json. Privé ou public

<img width="960" height="540" alt="messagerie-upload-fichier" src="https://github.com/user-attachments/assets/2bd9e005-17a5-4b33-91f9-21e8bae6a491" />


### Search fiche public

<img width="960" height="540" alt="messagerie-search-fichier" src="https://github.com/user-attachments/assets/c77b84e1-9004-42ae-8735-0c1bf8a886e1" />


## ✨ Fonctionnalités

- ✅ **Authentification sécurisée** (inscription/connexion)
- 📝 **Création de posts** avec markdown de base
- ❤️ **Système de likes** interactif
- 🔒 **Gestion de profil** (changement mot de passe)
- 🗂️ **Partage de fichier** privée ou public (.csv .json ou excel)
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

Les Améliorations sont les bienvenues ! Ouvrez une issue pour discuter. Merci du soutient. 

V1.1 Développé avec ❤️ par berru-g et deepseek 2025
