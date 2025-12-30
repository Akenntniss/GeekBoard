#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
Test Scraping Parallèle Utopya - 2 Navigateurs + Headless
"""

import csv
import time
import os
import threading
import queue
import logging
from datetime import datetime
from playwright.sync_api import sync_playwright, TimeoutError as PlaywrightTimeout

# Configuration du logging
logging.basicConfig(
    level=logging.INFO,
    format='%(asctime)s - %(levelname)s - [%(threadName)s] - %(message)s',
    handlers=[
        logging.FileHandler('utopya_parallel_test.log'),
        logging.StreamHandler()
    ]
)
logger = logging.getLogger(__name__)

# Configuration
NUM_WORKERS = 2
MAX_PRODUCTS = 100
OUTPUT_FILE = "test_parallel_100.csv"
SESSION_FILE = "utopya_session.json"

# File d'attente pour les URLs
url_queue = queue.Queue()
results = []
results_lock = threading.Lock()
stats = {'success': 0, 'errors': 0}

def scrape_product(page, product_url):
    """Extrait les données d'un produit"""
    try:
        page.goto(product_url, timeout=20000)
        time.sleep(1.5)  # Attente de chargement
        
        product_data = {
            'PRODUIT': '',
            'CATEGORIE': '',
            'COMPATIBILITE': '',
            'PRIX': '',
            'URL': product_url,
            'SKU': '',
            'DATE_EXTRACTION': datetime.now().strftime('%Y-%m-%d %H:%M:%S')
        }
        
        # Attente du chargement
        try:
            page.wait_for_load_state("domcontentloaded", timeout=15000)
            # Attente du titre
            page.locator('h1').first.wait_for(state="visible", timeout=10000)
        except:
            pass
        
        # Extraction catégorie depuis body class
        try:
            body_class = page.locator('body').get_attribute('class') or ''
            import re
            match = re.search(r'categorypath-([a-z0-9-]+)', body_class)
            if match:
                cat_path = match.group(1)
                parts = cat_path.split('-')
                product_data['CATEGORIE'] = ' > '.join([p.capitalize() for p in parts])
        except:
            pass
        
        # Fallback catégorie
        if not product_data['CATEGORIE']:
            try:
                marque = page.locator('li.attr-fabricant .data').first
                if marque.count() > 0:
                    product_data['CATEGORIE'] = marque.inner_text().strip()
            except:
                pass
        
        # Compatibilité
        try:
            compat_links = page.locator('li.attr-compatibilite a.fake-link').all()
            compat_list = [link.inner_text().strip() for link in compat_links if link.inner_text().strip()]
            product_data['COMPATIBILITE'] = ', '.join(compat_list)
        except:
            pass
        
        # Nom du produit
        selectors = ['h1.product.block-title span.base', '.product-info-main h1 span', '.page-title span.base']
        for sel in selectors:
            try:
                el = page.locator(sel).first
                if el.count() > 0 and el.is_visible():
                    txt = el.inner_text().strip()
                    if txt and 'www.' not in txt.lower() and 'utopya' not in txt.lower():
                        product_data['PRODUIT'] = txt
                        break
            except:
                continue
        
        # Prix
        try:
            price_el = page.locator('.product_pricebloc .price-box .price').first
            if price_el.count() > 0 and price_el.is_visible():
                product_data['PRIX'] = price_el.inner_text().strip()
        except:
            pass
        
        # SKU
        try:
            sku_el = page.locator('li.attr-sku .data').first
            if sku_el.count() > 0:
                product_data['SKU'] = sku_el.inner_text().strip()
        except:
            pass
        
        return product_data
        
    except Exception as e:
        logger.error(f"Erreur scraping {product_url}: {e}")
        return None

def worker(worker_id):
    """Worker qui scrape les produits de la queue"""
    logger.info(f"Worker {worker_id} démarré")
    
    with sync_playwright() as p:
        browser = p.chromium.launch(headless=False)  # Mode visible pour éviter détection
        
        # Charge la session si disponible
        storage_state = SESSION_FILE if os.path.exists(SESSION_FILE) else None
        context = browser.new_context(
            user_agent='Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36',
            storage_state=storage_state
        )
        page = context.new_page()
        
        try:
            while True:
                try:
                    product_url = url_queue.get(timeout=5)
                except queue.Empty:
                    break
                
                logger.info(f"Scraping: {product_url[:60]}...")
                product_data = scrape_product(page, product_url)
                
                if product_data and product_data.get('PRODUIT'):
                    with results_lock:
                        results.append(product_data)
                        stats['success'] += 1
                    logger.info(f"✓ {product_data['PRODUIT'][:40]} - {product_data['PRIX']}")
                else:
                    with results_lock:
                        stats['errors'] += 1
                    logger.warning(f"✗ Échec: {product_url}")
                
                url_queue.task_done()
                
        finally:
            browser.close()
    
    logger.info(f"Worker {worker_id} terminé")

def get_sample_urls(count=100):
    """Récupère des URLs de produits pour le test"""
    logger.info(f"Récupération de {count} URLs de produits...")
    
    with sync_playwright() as p:
        browser = p.chromium.launch(headless=False)  # Mode visible
        storage_state = SESSION_FILE if os.path.exists(SESSION_FILE) else None
        context = browser.new_context(
            user_agent='Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36',
            storage_state=storage_state
        )
        page = context.new_page()
        
        try:
            # Va sur une catégorie avec beaucoup de produits
            page.goto("https://www.utopya.fr/apple/iphone.html", timeout=30000)
            time.sleep(2)
            
            # Scroll pour charger plus de produits
            for _ in range(5):
                page.evaluate("window.scrollTo(0, document.body.scrollHeight)")
                time.sleep(1)
            
            # Récupère les liens
            links = page.locator('a.product-item-link').all()
            urls = []
            for link in links[:count]:
                try:
                    url = link.get_attribute('href')
                    if url:
                        urls.append(url)
                except:
                    continue
            
            logger.info(f"✓ {len(urls)} URLs récupérées")
            return urls
            
        finally:
            browser.close()

def save_results():
    """Sauvegarde les résultats"""
    if not results:
        logger.warning("Aucun résultat à sauvegarder")
        return
    
    fieldnames = ['PRODUIT', 'CATEGORIE', 'COMPATIBILITE', 'PRIX', 'URL', 'SKU', 'DATE_EXTRACTION']
    with open(OUTPUT_FILE, 'w', newline='', encoding='utf-8') as f:
        writer = csv.DictWriter(f, fieldnames=fieldnames)
        writer.writeheader()
        writer.writerows(results)
    
    logger.info(f"✓ {len(results)} produits sauvegardés dans {OUTPUT_FILE}")

def main():
    logger.info("=" * 80)
    logger.info("TEST SCRAPING PARALLÈLE - 2 WORKERS + HEADLESS")
    logger.info("=" * 80)
    
    start_time = time.time()
    
    # Vérification session
    if not os.path.exists(SESSION_FILE):
        logger.error(f"❌ Fichier de session non trouvé: {SESSION_FILE}")
        logger.error("Veuillez d'abord lancer le scraper normal pour créer la session.")
        return
    
    # Récupération des URLs
    urls = get_sample_urls(MAX_PRODUCTS)
    
    if not urls:
        logger.error("Aucune URL récupérée")
        return
    
    # Ajoute les URLs à la queue
    for url in urls:
        url_queue.put(url)
    
    logger.info(f"\n{'=' * 80}")
    logger.info(f"LANCEMENT DE {NUM_WORKERS} WORKERS SUR {len(urls)} PRODUITS")
    logger.info(f"{'=' * 80}\n")
    
    # Lance les workers
    threads = []
    for i in range(NUM_WORKERS):
        t = threading.Thread(target=worker, args=(i+1,), name=f"Worker-{i+1}")
        t.start()
        threads.append(t)
    
    # Attend la fin
    for t in threads:
        t.join()
    
    # Sauvegarde
    save_results()
    
    # Stats
    elapsed = time.time() - start_time
    logger.info(f"\n{'=' * 80}")
    logger.info(f"RÉSULTATS:")
    logger.info(f"  - Produits scrapés: {stats['success']}")
    logger.info(f"  - Erreurs: {stats['errors']}")
    logger.info(f"  - Temps total: {elapsed:.1f}s")
    logger.info(f"  - Vitesse: {stats['success'] / elapsed * 60:.1f} produits/min")
    logger.info(f"{'=' * 80}")

if __name__ == "__main__":
    main()
