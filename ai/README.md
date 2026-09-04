# Module IA — prédiction du risque d’absence

Ce module ajoute une prédiction par **Random Forest** sans remplacer le système
de pointage actuel. Il lit `employees`, `attendance` et `settings`, puis crée deux
tables dédiées :

- `ai_training_data` : données préparées pour entraîner le modèle ;
- `absence_predictions` : résultats calculés pour les employés.

## Avant de commencer

- Synchroniser les terminaux ZKTeco afin que MySQL contienne les derniers pointages.
- Disposer idéalement de plusieurs mois d’historique.
- Par défaut, les jours ouvrables sont du lundi au vendredi. Ajouter les jours
  fériés dans `ai/holidays.txt`, avec une date `YYYY-MM-DD` par ligne.

## Installation sous Windows / XAMPP

Dans le terminal VS Code, à la racine du projet :

```powershell
py -m venv .venv
.\.venv\Scripts\Activate.ps1
python -m pip install -r ai\requirements.txt
python ai\predict_absences.py
```

Le script crée automatiquement la table MySQL nécessaire, entraîne le modèle,
enregistre `ai/model.joblib` localement et sauvegarde les prédictions dans MySQL.

### Démonstration sans historique réel

Pour vérifier immédiatement l’interface sans ajouter de faux pointages dans les
tables réelles :

```powershell
python ai\predict_absences.py --demo
```

Ce mode fabrique un historique d’apprentissage, l’enregistre dans la table
`ai_training_data`, puis entraîne le modèle. Il conserve les vrais noms d’employés
pour l’affichage et marque les résultats **Demo data**. Il ne modifie ni
`employees` ni `attendance`.

Ouvrir ensuite :

```text
http://localhost/clocking/dashboard/ai_predictions.php
```

Si le dossier placé dans `htdocs` porte un autre nom, remplacer `clocking` dans
l’adresse.

## Base MySQL personnalisée

Le module utilise les mêmes variables d’environnement que ChronoX :

```powershell
$env:CHRONOX_DB_HOST="127.0.0.1"
$env:CHRONOX_DB_PORT="3306"
$env:CHRONOX_DB_NAME="clocking"
$env:CHRONOX_DB_USER="root"
$env:CHRONOX_DB_PASS=""
python ai\predict_absences.py
```

Ne jamais enregistrer un vrai mot de passe dans GitHub.

## Fonctionnement du modèle

Le modèle étudie uniquement l’historique antérieur à la journée prédite : taux
d’absence sur 7 et 30 jours ouvrables, fréquence des retards, séries de présences
ou d’absences, jour de la semaine, mois et département.

Il faut au minimum 20 jours ouvrables par employé, 30 exemples utilisables et des
exemples de présence comme d’absence. Cette limite peut être ajustée avec
`CHRONOX_AI_MIN_HISTORY_DAYS`, mais une valeur trop faible rend le résultat peu fiable.

Les niveaux affichés sont :

- `low` : moins de 35 % ;
- `medium` : de 35 % à moins de 65 % ;
- `high` : 65 % ou plus.

Une prédiction est une aide à la planification. Elle ne doit pas être utilisée
seule pour sanctionner ou évaluer un employé.

## Exécution automatique facultative

Dans le Planificateur de tâches Windows, lancer chaque soir :

```text
C:\xampp\htdocs\clocking\.venv\Scripts\python.exe
```

avec l’argument :

```text
C:\xampp\htdocs\clocking\ai\predict_absences.py
```

et le dossier de démarrage :

```text
C:\xampp\htdocs\clocking
```
