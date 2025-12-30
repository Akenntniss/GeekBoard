#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
Scraper Utopya.fr avec Playwright - Stable sur Mac ARM64
Extrait : PRODUIT, PRIX, URL, REFERENCE, SKU
"""

import csv
import time
import random
import logging
import argparse
import os
from datetime import datetime
from playwright.sync_api import sync_playwright, TimeoutError as PlaywrightTimeout

# Configuration du logging
logging.basicConfig(
    level=logging.INFO,
    format='%(asctime)s - %(levelname)s - %(message)s',
    handlers=[
        logging.FileHandler('utopya_playwright.log'),
        logging.StreamHandler()
    ]
)
logger = logging.getLogger(__name__)


class UtopyaScraperPlaywright:
    """Scraper Utopya avec Playwright"""
    
    def __init__(self, output_file='utopya_catalogue.csv', headless=False):
        self.base_url = "https://www.utopya.fr"
        self.output_file = output_file
        self.products_scraped = 0
        self.errors = 0
        self.headless = headless
        self.scraped_urls = set()  # URLs déjà scrapées
        
        # Initialisation CSV et chargement des URLs existantes
        self._init_csv()
        self._load_existing_urls()
        
    def _init_csv(self):
        """Initialise le fichier CSV"""
        try:
            with open(self.output_file, 'r', encoding='utf-8') as f:
                logger.info(f"Fichier CSV existant : {self.output_file}")
        except FileNotFoundError:
            with open(self.output_file, 'w', newline='', encoding='utf-8') as f:
                writer = csv.DictWriter(f, fieldnames=['PRODUIT', 'CATEGORIE', 'COMPATIBILITE', 'PRIX', 'URL', 'REFERENCE', 'SKU', 'DATE_EXTRACTION'])
                writer.writeheader()
            logger.info(f"Nouveau fichier CSV créé : {self.output_file}")
    
    def _load_existing_urls(self):
        """Charge les URLs déjà scrapées depuis le CSV"""
        try:
            with open(self.output_file, 'r', encoding='utf-8') as f:
                reader = csv.DictReader(f)
                for row in reader:
                    if 'URL' in row and row['URL']:
                        self.scraped_urls.add(row['URL'])
            logger.info(f"✓ {len(self.scraped_urls)} URLs déjà scrapées chargées (seront ignorées)")
        except Exception as e:
            logger.warning(f"Impossible de charger les URLs existantes : {e}")
    
    def _random_delay(self, min_sec=0.3, max_sec=0.8):
        """Pause aléatoire (réduite pour plus de vitesse)"""
        time.sleep(random.uniform(min_sec, max_sec))
    
    def login_manual(self, page, context):
        """Connexion manuelle avec sauvegarde de session"""
        session_file = "utopya_session.json"
        
        # Vérifie si une session sauvegardée existe et est valide
        if os.path.exists(session_file):
            try:
                # La session a été chargée dans le context, vérifions si elle est valide
                page.goto(f"{self.base_url}/customer/account/")
                time.sleep(2)
                # Si on est redirigé vers login, la session est expirée
                if "/login" not in page.url and "Mon compte" in page.content():
                    logger.info("✓ Session existante valide - Pas besoin de se reconnecter !")
                    return True
                else:
                    logger.info("Session expirée, nouvelle connexion nécessaire...")
            except:
                pass
        
        # Connexion manuelle nécessaire
        logger.info("=" * 80)
        logger.info("MODE CONNEXION MANUELLE")
        logger.info("=" * 80)
        logger.info("Connectez-vous dans le navigateur...")
        
        page.goto(f"{self.base_url}/customer/account/login/")
        
        input("\n🔐 Une fois connecté (et redirigé vers l'accueil ou mon compte), appuyez sur ENTRÉE...\n")
        
        # Sauvegarde la session pour les prochaines fois
        try:
            context.storage_state(path=session_file)
            logger.info(f"✓ Session sauvegardée dans {session_file}")
        except Exception as e:
            logger.warning(f"Impossible de sauvegarder la session : {e}")
        
        logger.info("✓ Continuation du script...")
        return True
    
    def get_all_categories(self, page):
        """Extrait les catégories"""
        logger.info("Extraction des catégories...")
        page.goto(self.base_url)
        time.sleep(2)
        
        categories = []
        
        try:
            links = page.locator('nav a[href*=".html"]').all()
            
            for link in links:
                url = link.get_attribute('href')
                if url and url.startswith(self.base_url) and url != f"{self.base_url}/" and url not in categories:
                    categories.append(url)
                    logger.info(f"Catégorie : {url}")
                    
        except Exception as e:
            logger.error(f"Erreur : {e}")
        
        logger.info(f"Total : {len(categories)} catégories")
        return categories
    
    def get_products_from_category(self, page, category_url, max_pages=None):
        """Extrait les produits d'une catégorie (avec gestion infinite scroll)"""
        logger.info(f"Catégorie : {category_url}")
        products = []
        max_retries = 3
        
        # Navigation vers la catégorie
        for retry in range(max_retries):
            try:
                page.goto(category_url, timeout=30000)
                break
            except Exception as e:
                if retry < max_retries - 1:
                    logger.warning(f"Erreur réseau, retry {retry+1}/{max_retries}...")
                    time.sleep(3)
                else:
                    logger.error(f"Échec navigation après {max_retries} tentatives : {e}")
                    return products
        
        time.sleep(2)
        
        # Attente du chargement complet (JavaScript/Knockout.js)
        try:
            page.wait_for_load_state("networkidle", timeout=15000)
        except:
            pass
        
        try:
            # Attend que les produits soient chargés (timeout augmenté)
            page.wait_for_selector('a.product-item-link', timeout=20000)
        except PlaywrightTimeout:
            logger.info("Aucun produit trouvé")
            return products
        except Exception as e:
            logger.error(f"Erreur attente produits : {e}")
            return products
        
        # Gestion du lazy loading / infinite scroll
        try:
            last_count = 0
            stable_count = 0
            max_scroll_attempts = 50
            
            for scroll_attempt in range(max_scroll_attempts):
                current_count = page.locator('a.product-item-link').count()
                
                if current_count == last_count:
                    stable_count += 1
                    if stable_count >= 3:
                        break
                else:
                    stable_count = 0
                    last_count = current_count
                
                # Scroll avec gestion d'erreur
                try:
                    page.evaluate("window.scrollTo(0, document.body.scrollHeight)")
                except:
                    break
                time.sleep(1)
        except Exception as e:
            logger.warning(f"Erreur scroll : {e}")
        
        # Récupère tous les liens produits
        try:
            links = page.locator('a.product-item-link').all()
            
            for link in links:
                try:
                    product_url = link.get_attribute('href')
                    if product_url and product_url not in products:
                        products.append(product_url)
                except:
                    continue
        except Exception as e:
            logger.error(f"Erreur récupération liens : {e}")
        
        logger.info(f"Total : {len(products)} produits (après scroll)")
        return products
    
    def load_cached_urls(self):
        """Charge les URLs depuis le cache"""
        cache_file = "utopya_products_cache.json"
        if os.path.exists(cache_file):
            try:
                import json
                with open(cache_file, 'r') as f:
                    urls = json.load(f)
                logger.info(f"✓ {len(urls)} URLs chargées depuis le cache {cache_file}")
                return urls
            except Exception as e:
                logger.error(f"Erreur chargement cache : {e}")
        return []

    def save_cached_urls(self, urls):
        """Sauvegarde les URLs dans le cache"""
        try:
            import json
            with open("utopya_products_cache.json", 'w') as f:
                json.dump(urls, f)
            logger.info(f"URLs sauvegardées dans utopya_products_cache.json")
        except Exception as e:
            logger.error(f"Erreur sauvegarde cache : {e}")

    def get_products_from_category(self, page, category_url):
        """Extrait les produits d'une catégorie (avec pagination lazy loading)"""
        logger.info(f"Catégorie : {category_url}")
        
        products = []
        
        try:
            page.goto(category_url, timeout=30000)
            time.sleep(1)
            
            # Accepte les cookies si présents
            try:
                cookie_btn = page.get_by_text("OK pour moi").first
                if cookie_btn.is_visible():
                    cookie_btn.click()
            except:
                pass
            
            # Gestion du lazy loading / infinite scroll
            try:
                page.wait_for_load_state("domcontentloaded", timeout=15000)
                # Attente du titre h1 pour être sûr que la page est chargée
                page.locator('h1').first.wait_for(state="visible", timeout=15000)
            except:
                logger.warning(f"Timeout chargement page (attente titre h1) : {page.url}")
            
            time.sleep(2)
            
            # Attente du chargement complet (JavaScript/Knockout.js)
            try:
                page.wait_for_load_state("networkidle", timeout=15000)
            except:
                pass
            
            try:
                # Attend que les produits soient chargés (timeout augmenté)
                page.wait_for_selector('a.product-item-link', timeout=20000)
            except PlaywrightTimeout:
                return products
            except Exception as e:
                logger.error(f"Erreur attente produits : {e}")
                return products
            
            # Scroll
            last_count = 0
            stable_count = 0
            max_scroll_attempts = 50
            
            for scroll_attempt in range(max_scroll_attempts):
                current_count = page.locator('a.product-item-link').count()
                
                if current_count == last_count:
                    stable_count += 1
                    if stable_count >= 3:
                        break
                else:
                    stable_count = 0
                
                last_count = current_count
                
                # Scroll avec gestion d'erreur
                try:
                    page.evaluate("window.scrollTo(0, document.body.scrollHeight)")
                except:
                    break
                time.sleep(1)
            
            # Récupère tous les liens produits
            try:
                links = page.locator('a.product-item-link').all()
                for link in links:
                    try:
                        product_url = link.get_attribute('href')
                        if product_url and product_url not in products:
                            products.append(product_url)
                    except:
                        continue
            except Exception as e:
                logger.error(f"Erreur récupération liens : {e}")
            
            logger.info(f"Total : {len(products)} produits (après scroll)")
            return products
            
        except Exception as e:
            logger.error(f"Erreur scraping catégorie {category_url}: {e}")
            return products

    def scrape_product(self, page, product_url):
        """Extrait les données d'un produit"""
        logger.info(f"Produit : {product_url}")
        
        try:
            page.goto(product_url, timeout=15000)
            time.sleep(0.8)  # Réduit pour plus de vitesse
            
            # Gestion Popup Cookies
            try:
                cookie_btn = page.get_by_text("OK pour moi").first
                if cookie_btn.is_visible():
                    cookie_btn.click()
                    time.sleep(0.5)
            except:
                pass
            
            # Initialisation données
            product_data = {
                'PRODUIT': '',
                'CATEGORIE': '',
                'COMPATIBILITE': '',
                'PRIX': '',
                'URL': product_url,
                'REFERENCE': '',
                'SKU': '',
                'DATE_EXTRACTION': datetime.now().strftime('%Y-%m-%d %H:%M:%S')
            }
            
            # Attente chargement
            try:
                page.wait_for_load_state("domcontentloaded", timeout=15000)
                # Selecteur titre h1 robuste
                page.locator('h1.product.block-title span.base, .product-info-main h1 span, .page-title span.base').first.wait_for(state="visible", timeout=15000)
            except:
                logger.error(f"Timeout chargement page (attente titre h1) : {product_url}")
            

            
            # Extraction de la catégorie depuis la classe body categorypath-*
            try:
                body_class = page.locator('body').get_attribute('class') or ''
                import re
                match = re.search(r'categorypath-([a-z0-9-]+)', body_class)
                if match:
                    # Convertit "apple-iphone-iphone-15" en "Apple > iPhone > iPhone 15"
                    cat_path = match.group(1)
                    parts = cat_path.split('-')
                    formatted_parts = [p.capitalize() for p in parts]
                    product_data['CATEGORIE'] = ' > '.join(formatted_parts)
            except:
                pass
            
            # Fallback: Si pas de categorypath, utiliser la Marque (li.attr-fabricant)
            if not product_data['CATEGORIE']:
                try:
                    marque = page.locator('li.attr-fabricant .data').first
                    if marque.count() > 0 and marque.is_visible():
                        product_data['CATEGORIE'] = marque.inner_text().strip()
                except:
                    pass
            
            # Extraction de la compatibilité
            try:
                compat_links = page.locator('li.attr-compatibilite a.fake-link').all()
                compat_list = []
                for link in compat_links:
                    txt = link.inner_text().strip()
                    if txt:
                        compat_list.append(txt)
                product_data['COMPATIBILITE'] = ', '.join(compat_list)
            except:
                pass
            
            # Helper pour trouver le texte visible
            def get_visible_text(selector, name="Element"):
                logger.info(f"--- Recherche {name} : {selector} ---")
                elements = page.locator(selector)
                count = elements.count()
                
                for i in range(count):
                    el = elements.nth(i)
                    if el.is_visible():
                        txt = el.inner_text().strip()
                        if txt:
                            return txt
                return ""

            # Nom du produit - sélecteurs stricts pour éviter le header
            product_data['PRODUIT'] = get_visible_text('h1.product.block-title span.base', "Titre 1")
            if not product_data['PRODUIT']:
                product_data['PRODUIT'] = get_visible_text('.product-info-main h1 span', "Titre 2")
            if not product_data['PRODUIT']:
                product_data['PRODUIT'] = get_visible_text('.page-title span.base', "Titre 3")
            # Ignorer les valeurs invalides (URL, www, etc.)
            if product_data['PRODUIT'] and ('www.' in product_data['PRODUIT'].lower() or 'utopya' in product_data['PRODUIT'].lower()):
                product_data['PRODUIT'] = ''

            # Prix
            product_data['PRIX'] = get_visible_text('.product_pricebloc .price-box .price', "Prix Desktop")
            if not product_data['PRIX']:
                 product_data['PRIX'] = get_visible_text('.price-box .price', "Prix Générique")
            
            # SKU
            product_data['SKU'] = get_visible_text('li.attr-sku .data', "SKU")

            # Reference
            product_data['REFERENCE'] = get_visible_text('.attr-description_ref .data', "Ref")
            if not product_data['REFERENCE']:
                 try:
                     rows = page.locator('.additional-attributes tr')
                     for i in range(rows.count()):
                         row = rows.nth(i)
                         if row.is_visible() and "Référence" in row.inner_text():
                             product_data['REFERENCE'] = row.locator('td').inner_text().strip()
                             break
                 except: pass
            
            return product_data
            
        except Exception as e:
            logger.error(f"Erreur : {e}")
            self.errors += 1
            return None
    
    def save_to_csv(self, product_data):
        """Sauvegarde dans CSV"""
        if not product_data:
            return
        
        try:
            with open(self.output_file, 'a', newline='', encoding='utf-8') as f:
                writer = csv.DictWriter(f, fieldnames=['PRODUIT', 'CATEGORIE', 'COMPATIBILITE', 'PRIX', 'URL', 'REFERENCE', 'SKU', 'DATE_EXTRACTION'])
                writer.writerow(product_data)
            self.products_scraped += 1
            logger.info(f"→ Sauvegardé (total: {self.products_scraped})")
        except Exception as e:
            logger.error(f"Erreur sauvegarde : {e}")
    
    def run_full(self, limit=0):
        """Scraping complet (limit=0 pour tout scraper)"""
        logger.info("=" * 80)
        if limit > 0:
            logger.info(f"SCRAPING LIMITÉ À {limit} PRODUITS")
        else:
            logger.info("SCRAPING COMPLET DU CATALOGUE UTOPYA.FR")
        logger.info("=" * 80)
        
        start_time = time.time()
        
        with sync_playwright() as p:
            browser = p.chromium.launch(headless=self.headless)
            
            # Charge la session existante si disponible
            session_file = "utopya_session.json"
            storage_state = session_file if os.path.exists(session_file) else None
            context = browser.new_context(
                user_agent='Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36',
                storage_state=storage_state
            )
            page = context.new_page()
            
            try:
                # Connexion
                if not self.login_manual(page, context):
                    logger.error("Connexion annulée")
                    return
                
                # 1. Charge le cache existant
                cached_urls = set(self.load_cached_urls())
                all_products_set = cached_urls.copy()
                
                # 2. Scanne TOUJOURS les catégories pour trouver les manquants (sauf si limit > 0 et cache suffisant)
                if limit > 0 and len(cached_urls) >= limit:
                    logger.info("Cache suffisant pour la limite demandée. Pas de scan catégories.")
                else:
                    logger.info("Scan des catégories pour mise à jour du catalogue complet...")
                    categories = self.get_all_categories(page)
                    
                    if categories:
                        for i, cat_url in enumerate(categories, 1):
                            # Arrêt anticipé pour test
                            if limit > 0 and len(all_products_set) >= limit * 2:
                                break
                                
                            try:
                                logger.info(f"\n[{i}/{len(categories)}] - {len(all_products_set)} URLs connues")
                                products = self.get_products_from_category(page, cat_url)
                                for p in products:
                                    all_products_set.add(p)
                            except Exception as e:
                                logger.error(f"Erreur catégorie {cat_url}: {e}")
                                continue
                    
                    # Sauvegarde le cache mis à jour
                    self.save_cached_urls(list(all_products_set))
                
                all_products = list(all_products_set)
                logger.info(f"Total catalogue : {len(all_products)} produits")
                
                # Scraping
                # Filtre les URLs déjà scrapées
                new_products = [url for url in all_products if url not in self.scraped_urls]
                skipped = len(all_products) - len(new_products)
                
                # Applique la limite si spécifiée
                if limit > 0 and len(new_products) > limit:
                    new_products = new_products[:limit]
                    logger.info(f"Limité à {limit} produits pour ce test")
                
                logger.info(f"\n{'=' * 80}")
                logger.info(f"SCRAPING DE {len(new_products)} PRODUITS ({skipped} déjà scrapés, ignorés)")
                logger.info(f"{'=' * 80}\n")
                
                for i, product_url in enumerate(new_products, 1):
                    logger.info(f"\n[{i}/{len(new_products)}]")
                    product_data = self.scrape_product(page, product_url)
                    if product_data:
                        self.save_to_csv(product_data)
                    
                    if i % 100 == 0:
                        logger.info(f"\nCHECKPOINT : {i} traités\n")
                
            except KeyboardInterrupt:
                logger.warning("\nInterruption")
            except Exception as e:
                logger.error(f"Erreur fatale : {e}")
            finally:
                browser.close()
        
        # Stats
        elapsed = time.time() - start_time
        logger.info(f"\n{'=' * 80}")
        logger.info(f"Produits scrapés : {self.products_scraped}")
        logger.info(f"Erreurs : {self.errors}")
        logger.info(f"Temps : {elapsed/60:.2f} min")
        logger.info(f"Fichier : {self.output_file}")
        logger.info(f"{'=' * 80}\n")
    
    def run_test_product(self, product_url):
        """Test sur un produit"""
        logger.info("TEST : Un produit")
        
        with sync_playwright() as p:
            browser = p.chromium.launch(headless=self.headless)
            
            # Charge la session existante si disponible
            session_file = "utopya_session.json"
            storage_state = session_file if os.path.exists(session_file) else None
            context = browser.new_context(
                user_agent='Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36',
                storage_state=storage_state
            )
            page = context.new_page()
            
            try:
                if not self.login_manual(page, context):
                    return
                
                product_data = self.scrape_product(page, product_url)
                if product_data:
                    self.save_to_csv(product_data)
                    logger.info("\nDonnées :")
                    for key, value in product_data.items():
                        logger.info(f"  {key}: {value}")
            finally:
                browser.close()


def main():
    parser = argparse.ArgumentParser(description='Scraper Utopya avec Playwright')
    parser.add_argument('--full', action='store_true', help='Scraping complet')
    parser.add_argument('--test-product', type=str, help='Test sur un produit')
    parser.add_argument('--output', type=str, default='utopya_catalogue.csv', help='Fichier CSV')
    parser.add_argument('--visible', action='store_true', help='Mode visible')
    parser.add_argument('--limit', type=int, default=0, help='Limite de produits (0 = illimité)')
    
    args = parser.parse_args()
    
    scraper = UtopyaScraperPlaywright(
        output_file=args.output,
        headless=not args.visible
    )
    
    if args.test_product:
        scraper.run_test_product(args.test_product)
    elif args.full:
        scraper.run_full(limit=args.limit)
    else:
        parser.print_help()


if __name__ == "__main__":
    main()
