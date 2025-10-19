# Documentation - Limitations de l'API Google Custom Search

## ⚠️ **Limite importante** : Maximum 10 résultats par requête

L'API Google Custom Search a une **limitation technique** :
- **Maximum 10 résultats** par requête HTTP
- Le paramètre `num` est plafonné à 10

## 🔄 **Pour obtenir 20 résultats** :

Vous devez faire **2 requêtes HTTP** :
1. Requête 1 : `num=10&start=1` → Résultats 1-10
2. Requête 2 : `num=10&start=11` → Résultats 11-20

## 💰 **Coûts** :

- 2 requêtes = 2x plus cher
- Quota par jour : 100 requêtes gratuites
- Au-delà : $5 pour 1000 requêtes

## 🎯 **Recommandation actuelle** :

Pour le moment, nous utilisons **10 résultats par recherche** pour :
- ✅ Économiser le quota API
- ✅ Réduire les coûts
- ✅ Améliorer la vitesse de réponse

Si vous voulez absolument 20 résultats, il faudrait modifier le code pour faire 2 requêtes HTTP.
