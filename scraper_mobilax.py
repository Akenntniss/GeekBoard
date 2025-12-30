#!/usr/bin/env python3
"""
Scraper Mobilax.fr - Extraction du catalogue produits
Ce script ouvre le navigateur, attend votre connexion manuelle,
puis extrait les informations produits.
SUPPORTE LA REPRISE AUTOMATIQUE en cas d'interruption.
"""

import csv
import json
import time
import re
import os
from datetime import datetime
from playwright.sync_api import sync_playwright

# Configuration
BASE_URL = "https://www.mobilax.fr"
LOGIN_URL = f"{BASE_URL}/login"

OUTPUT_CSV = "mobilax_catalogue.csv"
OUTPUT_JSON = "mobilax_catalogue.json"


def load_existing_products():
    """Charge les produits déjà scrapés depuis le CSV existant"""
    existing_products = []
    existing_urls = set()
    
    if os.path.exists(OUTPUT_CSV):
        try:
            with open(OUTPUT_CSV, "r", encoding="utf-8") as f:
                reader = csv.DictReader(f)
                for row in reader:
                    existing_products.append(row)
                    existing_urls.add(row.get("url", ""))
            print(f"📂 {len(existing_products)} produits déjà scrapés trouvés dans {OUTPUT_CSV}")
        except Exception as e:
            print(f"⚠️ Erreur lecture CSV existant: {e}")
    
    return existing_products, existing_urls


def get_all_subcategories(page, base_category_url):
    """Récupère toutes les sous-catégories d'une catégorie principale"""
    print(f"  🔍 Exploration des sous-catégories de {base_category_url}...")
    page.goto(base_category_url, wait_until="domcontentloaded", timeout=15000)
    time.sleep(0.3)
    
    subcategories = set()
    subcategories.add(base_category_url)
    
    # Chercher les liens vers les sous-catégories
    links = page.query_selector_all("a[href]")
    for link in links:
        href = link.get_attribute("href")
        if href and base_category_url.replace(BASE_URL, "") in href:
            full_url = href if href.startswith("http") else BASE_URL + href
            if full_url.startswith(base_category_url) and full_url != base_category_url:
                subcategories.add(full_url)
    
    print(f"    ✅ {len(subcategories)} catégorie(s) trouvée(s)")
    return list(subcategories)


def scrape_category_page(page, category_url):
    """Scrape une page de catégorie et retourne les produits"""
    products = []
    
    # Trouver tous les produits sur la page
    # Sélecteur basé sur la structure: div contenant un lien avec image produit
    product_containers = page.query_selector_all("div.bg-white.p-2.shadow-sm, div[class*='rounded-xl'][class*='shadow-sm'], div.w-full.md\\:w-\\[240px\\]")
    
    if not product_containers:
        # Essayer de trouver les liens produits directement
        product_links = page.query_selector_all("a[href^='/'][href*='-']")
        seen_urls = set()
        
        for link in product_links:
            href = link.get_attribute("href")
            if not href or href in seen_urls:
                continue
            
            # Ignorer les liens de navigation/catégorie
            if any(x in href for x in ['/pieces-detachees', '/accessoires', '/protections', '/outillages', '/login', '/account', '/cart', '/page=']):
                if href.count('/') <= 2:  # C'est une catégorie, pas un produit
                    continue
            
            # Vérifier que c'est un lien produit (contient un tiret, longueur significative)
            if '-' in href and len(href) > 20:
                full_url = BASE_URL + href if not href.startswith("http") else href
                if full_url not in seen_urls:
                    seen_urls.add(full_url)
                    
                    # Essayer de récupérer le nom depuis le conteneur parent
                    parent = link
                    for _ in range(5):
                        parent = parent.query_selector("xpath=..")
                        if parent:
                            name_elem = parent.query_selector("div[class*='line-clamp'], span[class*='line-clamp']")
                            price_elem = parent.query_selector("div:has-text('€'), span:has-text('€')")
                            stock_elem = parent.query_selector("div:has-text('stock'), span:has-text('stock')")
                            
                            if name_elem:
                                products.append({
                                    "url": full_url,
                                    "name": name_elem.inner_text().strip() if name_elem else "",
                                    "price": "",
                                    "reference": "",
                                    "category": "",
                                    "stock": ""
                                })
                                break
                    else:
                        # Si on n'a pas trouvé de nom, ajouter quand même avec URL
                        products.append({
                            "url": full_url,
                            "name": "",
                            "price": "",
                            "reference": "",
                            "category": "",
                            "stock": ""
                        })
    
    return products


def get_all_product_urls_from_category(page, category_url):
    """Récupère toutes les URLs de produits d'une catégorie (avec pagination)"""
    all_urls = set()
    current_page = 1
    
    while True:
        url = f"{category_url}?page={current_page}" if current_page > 1 else category_url
        print(f"    📄 Page {current_page}...")
        
        try:
            page.goto(url, wait_until="domcontentloaded", timeout=15000)
        except:
            page.goto(url, wait_until="load", timeout=15000)
        time.sleep(0.2)
        
        # Récupérer tous les liens de la page
        links = page.query_selector_all("a[href]")
        page_urls = set()
        
        for link in links:
            href = link.get_attribute("href")
            if not href:
                continue
            
            # Construire l'URL complète
            if href.startswith("/"):
                full_url = BASE_URL + href
            elif href.startswith("http"):
                full_url = href
            else:
                continue
            
            # Filtrer pour ne garder que les URLs produits
            # Un produit a généralement une URL avec plusieurs segments et des tirets
            path = full_url.replace(BASE_URL, "")
            
            # Exclure les pages de navigation
            if any(x in path for x in ['?page=', '/login', '/account', '/cart', '/wishlist', '/search']):
                continue
            
            # Un produit a généralement un nom avec des tirets et est assez long
            if path.count('/') <= 1 and '-' in path and len(path) > 15:
                # Vérifier que ce n'est pas une catégorie principale
                if path not in ['/pieces-detachees', '/accessoires', '/protections', '/outillages']:
                    page_urls.add(full_url)
        
        if not page_urls:
            # Pas de nouveaux produits, on a fini
            break
        
        new_urls = page_urls - all_urls
        if not new_urls:
            # Aucune nouvelle URL, on a fait le tour
            break
        
        all_urls.update(new_urls)
        print(f"      ✅ {len(new_urls)} nouveaux produits")
        
        # Vérifier s'il y a une page suivante
        next_button = page.query_selector("a[aria-label='Page suivante'], a:has-text('Suivant'), a:has-text('›')")
        if not next_button:
            break
        
        # Vérifier si le bouton next est désactivé
        next_class = next_button.get_attribute("class") or ""
        if "disabled" in next_class or "cursor-not-allowed" in next_class:
            break
        
        current_page += 1
        
        # Limite de sécurité
        if current_page > 500:
            print("      ⚠️ Limite de pages atteinte (500)")
            break
    
    return list(all_urls)


def get_product_details(page, product_url):
    """Récupère les détails complets d'un produit"""
    try:
        page.goto(product_url, wait_until="domcontentloaded", timeout=10000)
    except:
        try:
            page.goto(product_url, wait_until="load", timeout=10000)
        except Exception as e:
            return None
    
    product = {
        "url": product_url,
        "name": "",
        "price": "",
        "reference": "",
        "category": "",
        "stock": ""
    }
    
    try:
        # NOM - h1.text-xl.font-bold.text-black
        name_elem = page.query_selector("h1.text-xl.font-bold, h1[class*='font-bold'], h1")
        if name_elem:
            product["name"] = name_elem.inner_text().strip()
        
        # PRIX - p.text-repair.font-bold ou élément contenant €HT
        price_elem = page.query_selector("p.text-repair.font-bold, p[class*='font-bold']:has-text('€')")
        if price_elem:
            price_text = price_elem.inner_text().strip()
            # Nettoyer le prix (extraire juste le nombre)
            price_match = re.search(r'(\d+[.,]\d+)', price_text)
            if price_match:
                product["price"] = price_match.group(1).replace(',', '.') + " €"
        
        if not product["price"]:
            # Essayer d'autres sélecteurs
            all_elements = page.query_selector_all("*")
            for elem in all_elements:
                try:
                    text = elem.inner_text()
                    if "€HT" in text or "€ HT" in text:
                        price_match = re.search(r'(\d+[.,]\d+)\s*€', text)
                        if price_match:
                            product["price"] = price_match.group(1).replace(',', '.') + " €"
                            break
                except:
                    continue
        
        # RÉFÉRENCE - p.text-xl.font-medium.text-black ou texte contenant "réf:"
        ref_elem = page.query_selector("p.text-xl.font-medium.text-black")
        if ref_elem:
            ref_text = ref_elem.inner_text().strip()
            product["reference"] = ref_text.replace("réf:", "").replace("Réf:", "").strip()
        
        if not product["reference"]:
            # Chercher le texte "réf:" dans la page
            page_content = page.content()
            ref_match = re.search(r'réf[:\s]*([A-Z0-9-]+)', page_content, re.IGNORECASE)
            if ref_match:
                product["reference"] = ref_match.group(1).strip()
        
        # STOCK - .bg-green-100 span ou texte "En stock" / "Rupture"
        stock_elem = page.query_selector(".bg-green-100 span, span:has-text('En stock')")
        if stock_elem:
            product["stock"] = "En stock"
        else:
            stock_elem = page.query_selector(".bg-red-100 span, span:has-text('Rupture'), span:has-text('rupture')")
            if stock_elem:
                product["stock"] = "Rupture de stock"
            else:
                # Chercher dans le texte
                page_text = page.inner_text("body")
                if "en stock" in page_text.lower():
                    product["stock"] = "En stock"
                elif "rupture" in page_text.lower():
                    product["stock"] = "Rupture de stock"
                else:
                    product["stock"] = "Non spécifié"
        
        # CATÉGORIE - Breadcrumb
        breadcrumb_links = page.query_selector_all("nav[aria-label='breadcrumb'] a, div[class*='breadcrumb'] a, .breadcrumb a")
        if breadcrumb_links:
            categories = []
            for link in breadcrumb_links:
                cat_text = link.inner_text().strip()
                if cat_text and cat_text.lower() not in ['accueil', 'home']:
                    categories.append(cat_text)
            product["category"] = " > ".join(categories) if categories else ""
        
        if not product["category"]:
            # Extraire la catégorie de l'URL
            path_parts = product_url.replace(BASE_URL, "").split("/")
            if len(path_parts) > 1:
                product["category"] = path_parts[1].replace("-", " ").title()
        
    except Exception as e:
        print(f"      ⚠️ Erreur extraction: {e}")
    
    return product


def main():
    print("=" * 60)
    print("🛒 SCRAPER MOBILAX.FR - Extraction du catalogue")
    print("=" * 60)
    print(f"📅 Date: {datetime.now().strftime('%Y-%m-%d %H:%M')}")
    
    with sync_playwright() as p:
        # Lancer le navigateur en mode visible
        browser = p.chromium.launch(headless=False)
        context = browser.new_context(
            viewport={"width": 1400, "height": 900},
            user_agent="Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36"
        )
        page = context.new_page()
        
        # Aller à la page de connexion
        print("\n🔐 Ouverture de la page de connexion...")
        page.goto(LOGIN_URL, wait_until="networkidle")
        
        print("\n" + "=" * 60)
        print("⏳ CONNECTEZ-VOUS MANUELLEMENT DANS LE NAVIGATEUR")
        print("   Une fois connecté, revenez ici et tapez 'ok' puis Entrée")
        print("=" * 60)
        
        # Attendre confirmation de l'utilisateur
        user_input = input("\n👉 Tapez 'ok' quand vous êtes connecté: ").strip().lower()
        
        if user_input != "ok":
            print("❌ Annulé par l'utilisateur")
            browser.close()
            return
        
        print("\n✅ Connexion confirmée ! Début du scraping...")
        
        # Catégories principales à explorer
        main_categories = [
            f"{BASE_URL}/pieces-detachees",
            f"{BASE_URL}/accessoires",
            f"{BASE_URL}/protections",
            f"{BASE_URL}/outillages",
        ]
        
        all_product_urls = set()
        
        # D'abord, collecter toutes les URLs de produits
        print("\n📋 PHASE 1: Collecte des URLs de produits")
        print("-" * 40)
        
        for main_cat in main_categories:
            cat_name = main_cat.split("/")[-1]
            print(f"\n📁 {cat_name.upper()}")
            
            # Explorer les sous-catégories
            subcategories = get_all_subcategories(page, main_cat)
            
            for subcat in subcategories:
                subcat_name = subcat.replace(BASE_URL, "")
                print(f"  📂 {subcat_name}")
                
                urls = get_all_product_urls_from_category(page, subcat)
                all_product_urls.update(urls)
        
        print(f"\n📊 Total URLs collectées: {len(all_product_urls)}")
        
        # Ensuite, récupérer les détails de chaque produit
        print("\n📋 PHASE 2: Extraction des détails produits")
        print("-" * 40)
        
        # Charger les produits déjà scrapés
        existing_products, existing_urls = load_existing_products()
        all_products = list(existing_products)  # Commencer avec les produits existants
        
        # Filtrer les URLs déjà scrapées
        urls_to_scrape = [url for url in all_product_urls if url not in existing_urls]
        skipped = len(all_product_urls) - len(urls_to_scrape)
        
        if skipped > 0:
            print(f"⏭️  {skipped} URLs déjà scrapées (ignorées)")
        print(f"📊 {len(urls_to_scrape)} URLs restantes à scraper")
        
        total = len(urls_to_scrape)
        
        for i, product_url in enumerate(urls_to_scrape, 1):
            print(f"  [{i}/{total}] {product_url[:60]}...")
            
            product = get_product_details(page, product_url)
            if product and product["name"]:
                all_products.append(product)
                print(f"    ✅ {product['name'][:40]} - {product['price']} - {product['stock']}")
            else:
                print(f"    ⚠️ Échec extraction")
            
            # Sauvegarde intermédiaire tous les 500 produits
            if i % 500 == 0:
                with open(OUTPUT_CSV, "w", newline="", encoding="utf-8") as f:
                    writer = csv.DictWriter(f, fieldnames=["name", "url", "price", "category", "reference", "stock"])
                    writer.writeheader()
                    writer.writerows(all_products)
                print(f"    💾 Sauvegarde intermédiaire: {len(all_products)} produits")
        
        browser.close()
        
        # Sauvegarder les résultats
        print("\n" + "=" * 60)
        print("💾 Sauvegarde des résultats...")
        
        # CSV
        with open(OUTPUT_CSV, "w", newline="", encoding="utf-8") as f:
            writer = csv.DictWriter(f, fieldnames=["name", "url", "price", "category", "reference", "stock"])
            writer.writeheader()
            writer.writerows(all_products)
        print(f"  ✅ CSV: {OUTPUT_CSV}")
        
        # JSON
        with open(OUTPUT_JSON, "w", encoding="utf-8") as f:
            json.dump(all_products, f, ensure_ascii=False, indent=2)
        print(f"  ✅ JSON: {OUTPUT_JSON}")
        
        print("\n" + "=" * 60)
        print(f"🎉 TERMINÉ ! {len(all_products)} produits extraits")
        print(f"   Fichiers créés dans: /Users/admin/Documents/GeekBoard/")
        print(f"   - {OUTPUT_CSV}")
        print(f"   - {OUTPUT_JSON}")
        print("=" * 60)


if __name__ == "__main__":
    main()
