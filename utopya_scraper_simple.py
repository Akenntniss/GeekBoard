#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
Scraper léger pour le catalogue Utopya.fr
Utilise requests + BeautifulSoup avec cookies exportés
"""

import csv
import json
import time
import random
import logging
import argparse
from datetime import datetime
from bs4 import BeautifulSoup
import requests
from urllib.parse import urljoin

# Configuration du logging
logging.basicConfig(
    level=logging.INFO,
    format='%(asctime)s - %(levelname)s - %(message)s',
    handlers=[
        logging.FileHandler('utopya_scraper_simple.log'),
        logging.StreamHandler()
    ]
)
logger = logging.getLogger(__name__)


class UtopyaScraperSimple:
    """Scraper léger pour Utopya.fr utilisant requests + BeautifulSoup"""
    
    def __init__(self, cookies_file='utopya_cookies.json', output_file='utopya_catalogue.csv'):
        """
        Initialise le scraper
        
        Args:
            cookies_file (str): Fichier JSON contenant les cookies exportés
            output_file (str): Nom du fichier CSV de sortie
        """
        self.base_url = "https://www.utopya.fr"
        self.output_file = output_file
        self.products_scraped = 0
        self.errors = 0
        
        # Chargement des cookies
        logger.info(f"Chargement des cookies depuis {cookies_file}...")
        try:
            with open(cookies_file, 'r') as f:
                cookies_data = json.load(f)
                
            # Convertir en format requests
            self.cookies = {}
            if isinstance(cookies_data, list):
                # Format [{"name": "...", "value": "..."}]
                for cookie in cookies_data:
                    self.cookies[cookie['name']] = cookie['value']
            elif isinstance(cookies_data, dict):
                # Format {"name": "value"}
                self.cookies = cookies_data
            else:
                raise Exception("Format de cookies non reconnu")
                
            logger.info(f"✓ {len(self.cookies)} cookies chargés")
        except FileNotFoundError:
            logger.error(f"Fichier {cookies_file} introuvable !")
            logger.error("Veuillez d'abord exporter vos cookies depuis Chrome.")
            logger.error("Instructions : connectez-vous sur Utopya.fr, puis dans la console Chrome (Cmd+Option+J) :")
            logger.error("copy(JSON.stringify(document.cookie.split('; ').map(c => {const [name, ...v] = c.split('='); return {name, value: v.join('=')};})))")
            logger.error(f"Ensuite, créez {cookies_file} et collez le contenu")
            raise
        
        # Headers pour imiter un vrai navigateur Chrome
        self.headers = {
            'User-Agent': 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36',
            'Accept': 'text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3;q=0.7',
            'Accept-Language': 'fr-FR,fr;q=0.9,en-US;q=0.8,en;q=0.7',
            'Accept-Encoding': 'gzip, deflate, br,zstd',
            'DNT': '1',
            'Connection': 'keep-alive',
            'Upgrade-Insecure-Requests': '1',
            'Sec-Fetch-Dest': 'document',
            'Sec-Fetch-Mode': 'navigate',
            'Sec-Fetch-Site': 'none',
            'Sec-Fetch-User': '?1',
            'Sec-Ch-Ua': '"Chromium";v="143", "Not-A.Brand";v="99"',
            'Sec-Ch-Ua-Mobile': '?0',
            'Sec-Ch-Ua-Platform': '"macOS"',
            'Cache-Control': 'max-age=0'
        }
        
        # Session requests
        self.session = requests.Session()
        self.session.headers.update(self.headers)
        self.session.cookies.update(self.cookies)
        
        # Initialisation du fichier CSV
        self._init_csv()
        
    def _init_csv(self):
        """Initialise le fichier CSV avec les en-têtes"""
        try:
            with open(self.output_file, 'r', encoding='utf-8') as f:
                logger.info(f"Fichier CSV existant trouvé : {self.output_file}")
        except FileNotFoundError:
            with open(self.output_file, 'w', newline='', encoding='utf-8') as f:
                writer = csv.DictWriter(f, fieldnames=['PRODUIT', 'PRIX', 'URL', 'REFERENCE', 'SKU', 'DATE_EXTRACTION'])
                writer.writeheader()
            logger.info(f"Nouveau fichier CSV créé : {self.output_file}")
    
    def _random_delay(self, min_seconds=2, max_seconds=4):
        """Pause aléatoire pour éviter le bannissement"""
        delay = random.uniform(min_seconds, max_seconds)
        time.sleep(delay)
    
    def _get_page(self, url, referer=None):
        """
        Récupère une page avec gestion d'erreurs
        
        Args:
            url (str): URL à récupérer
            referer (str): URL de référence (page précédente)
            
        Returns:
            BeautifulSoup: Objet soup ou None en cas d'erreur
        """
        try:
            headers = self.headers.copy()
            if referer:
                headers['Referer'] = referer
            
            response = self.session.get(url, headers=headers, timeout=30)
            response.raise_for_status()
            return BeautifulSoup(response.content, 'html.parser')
        except Exception as e:
            logger.error(f"Erreur lors de la récupération de {url} : {e}")
            return None
    
    def get_all_categories(self):
        """
        Extrait toutes les URLs de catégories depuis la page d'accueil
        
        Returns:
            list: Liste des URLs de catégories
        """
        logger.info("Extraction des catégories depuis la page d'accueil...")
        soup = self._get_page(self.base_url)
        if not soup:
            return []
        
        categories = []
        
        # Cherche tous les liens dans le menu principal
        nav_links = soup.select('nav a[href*=".html"]')
        
        for link in nav_links:
            url = link.get('href')
            if url:
                # Convertir en URL absolue si nécessaire
                if not url.startswith('http'):
                    url = urljoin(self.base_url, url)
                    
                if url not in categories and url != self.base_url + '/':
                    categories.append(url)
                    logger.info(f"Catégorie trouvée : {url}")
        
        logger.info(f"Total de {len(categories)} catégories trouvées")
        return categories
    
    def get_products_from_category(self, category_url, max_pages=None):
        """
        Extrait toutes les URLs de produits depuis une catégorie
        
        Args:
            category_url (str): URL de la catégorie
            max_pages (int): Nombre maximum de pages (None = toutes)
            
        Returns:
            list: Liste des URLs de produits
        """
        logger.info(f"Extraction des produits depuis : {category_url}")
        products = []
        page = 1
        
        while True:
            if max_pages and page > max_pages:
                break
            
            # Construction de l'URL avec pagination
            if page == 1:
                url = category_url
            else:
                url = f"{category_url}?p={page}"
            
            logger.info(f"Scraping page {page} : {url}")
            soup = self._get_page(url)
            if not soup:
                break
            
            self._random_delay()
            
            # Extrait tous les liens produits
            product_links = soup.select('li.item.product a.product-item-link')
            
            if not product_links:
                logger.info("Aucun produit trouvé, fin de la pagination")
                break
            
            page_products = []
            for link in product_links:
                product_url = link.get('href')
                if product_url and product_url not in products:
                    products.append(product_url)
                    page_products.append(product_url)
            
            logger.info(f"  → {len(page_products)} produits trouvés sur cette page")
            
            # Vérifie s'il y a une page suivante
            next_button = soup.select('.pages-items .action.next')
            if not next_button:
                logger.info("Dernière page atteinte")
                break
            
            page += 1
        
        logger.info(f"Total de {len(products)} produits trouvés dans cette catégorie")
        return products
    
    def scrape_product(self, product_url, referer=None):
        """
        Extrait les données d'un produit
        
        Args:
            product_url (str): URL du produit
            referer (str): URL de référence (catégorie d'origine)
            
        Returns:
            dict: Données du produit ou None en cas d'erreur
        """
        logger.info(f"Scraping produit : {product_url}")
        
        soup = self._get_page(product_url, referer=referer)
        if not soup:
            self.errors += 1
            return None
        
        self._random_delay()
        
        product_data = {
            'PRODUIT': '',
            'PRIX': '',
            'URL': product_url,
            'REFERENCE': '',
            'SKU': '',
            'DATE_EXTRACTION': datetime.now().strftime('%Y-%m-%d %H:%M:%S')
        }
        
        # Extraction du nom du produit
        try:
            name_elem = soup.select_one('h1.page-title span.base')
            if name_elem:
                product_data['PRODUIT'] = name_elem.get_text(strip=True)
        except Exception as e:
            logger.warning(f"Nom du produit non trouvé : {e}")
        
        # Extraction du prix
        try:
            price_elem = soup.select_one('.product-info-main .price-wrapper .price')
            if price_elem:
                product_data['PRIX'] = price_elem.get_text(strip=True)
            else:
                product_data['PRIX'] = 'N/A'
        except Exception as e:
            logger.warning(f"Prix non trouvé : {e}")
            product_data['PRIX'] = 'N/A'
        
        # Extraction du SKU
        try:
            sku_elem = soup.select_one('.product-info-main .sku .value')
            if sku_elem:
                product_data['SKU'] = sku_elem.get_text(strip=True)
        except Exception as e:
            logger.warning(f"SKU non trouvé : {e}")
        
        # Extraction de la référence
        try:
            # Cherche dans les attributs techniques
            attr_rows = soup.select('.product-info-main .additional-attributes tr')
            for row in attr_rows:
                label_elem = row.select_one('th')
                value_elem = row.select_one('td')
                if label_elem and value_elem:
                    label = label_elem.get_text(strip=True).lower()
                    if 'référence' in label or 'reference' in label or 'fabricant' in label:
                        product_data['REFERENCE'] = value_elem.get_text(strip=True)
                        break
        except Exception as e:
            logger.warning(f"Référence non trouvée : {e}")
        
        logger.info(f"✓ Produit extrait : {product_data['PRODUIT']}")
        return product_data
    
    def save_to_csv(self, product_data):
        """Sauvegarde les données d'un produit dans le CSV"""
        if not product_data:
            return
        
        try:
            with open(self.output_file, 'a', newline='', encoding='utf-8') as f:
                writer = csv.DictWriter(f, fieldnames=['PRODUIT', 'PRIX', 'URL', 'REFERENCE', 'SKU', 'DATE_EXTRACTION'])
                writer.writerow(product_data)
            self.products_scraped += 1
            logger.info(f"→ Produit sauvegardé (total: {self.products_scraped})")
        except Exception as e:
            logger.error(f"Erreur lors de la sauvegarde : {e}")
    
    def run_full(self):
        """Lance le scraping complet du catalogue"""
        logger.info("=" * 80)
        logger.info("DÉMARRAGE DU SCRAPING COMPLET DU CATALOGUE UTOPYA.FR")
        logger.info("=" * 80)
        
        start_time = time.time()
        
        try:
            # 1. Extraction des catégories
            categories = self.get_all_categories()
            
            if not categories:
                logger.error("Aucune catégorie trouvée")
                return
            
            # 2. Pour chaque catégorie, extraire les produits
            all_products = []
            for i, category_url in enumerate(categories, 1):
                logger.info(f"\n[{i}/{len(categories)}] Traitement : {category_url}")
                products = self.get_products_from_category(category_url)
                all_products.extend(products)
            
            # 3. Scraper chaque produit
            logger.info(f"\n{'=' * 80}")
            logger.info(f"SCRAPING DE {len(all_products)} PRODUITS")
            logger.info(f"{'=' * 80}\n")
            
            for i, product_url in enumerate(all_products, 1):
                logger.info(f"\n[{i}/{len(all_products)}] Produit {i}")
                product_data = self.scrape_product(product_url)
                if product_data:
                    self.save_to_csv(product_data)
                
                # Checkpoint
                if i % 50 == 0:
                    logger.info(f"\nCHECKPOINT : {i} produits traités, {self.products_scraped} sauvegardés\n")
        
        except KeyboardInterrupt:
            logger.warning("\n\nInterruption par l'utilisateur")
        except Exception as e:
            logger.error(f"Erreur fatale : {e}")
        
        # Statistiques
        elapsed_time = time.time() - start_time
        logger.info(f"\n{'=' * 80}")
        logger.info("SCRAPING TERMINÉ")
        logger.info(f"Produits scrapés : {self.products_scraped}")
        logger.info(f"Erreurs : {self.errors}")
        logger.info(f"Temps : {elapsed_time/60:.2f} minutes")
        logger.info(f"Fichier : {self.output_file}")
        logger.info(f"{'=' * 80}\n")
    
    def run_test_product(self, product_url):
        """Test sur un produit"""
        logger.info("TEST : Scraping d'un seul produit")
        product_data = self.scrape_product(product_url)
        if product_data:
            self.save_to_csv(product_data)
            logger.info("\nDonnées extraites :")
            for key, value in product_data.items():
                logger.info(f"  {key}: {value}")
    
    def run_test_category(self, category_url, max_products=10):
        """Test sur une catégorie"""
        logger.info(f"TEST : Scraping d'une catégorie (max {max_products} produits)")
        products = self.get_products_from_category(category_url, max_pages=2)
        
        products_to_scrape = products[:max_products]
        logger.info(f"\nScraping de {len(products_to_scrape)} produits...")
        
        for i, product_url in enumerate(products_to_scrape, 1):
            logger.info(f"\n[{i}/{len(products_to_scrape)}]")
            product_data = self.scrape_product(product_url)
            if product_data:
                self.save_to_csv(product_data)
        
        logger.info(f"\nTest terminé : {self.products_scraped} produits sauvegardés")


def main():
    """Point d'entrée"""
    parser = argparse.ArgumentParser(description='Scraper léger pour Utopya.fr')
    parser.add_argument('--full', action='store_true', help='Scraping complet')
    parser.add_argument('--test-product', type=str, help='Test sur un produit (URL)')
    parser.add_argument('--test-category', type=str, help='Test sur une catégorie (URL)')
    parser.add_argument('--cookies', type=str, default='utopya_cookies.json', help='Fichier cookies JSON')
    parser.add_argument('--output', type=str, default='utopya_catalogue.csv', help='Fichier CSV sortie')
    
    args = parser.parse_args()
    
    # Initialisation
    scraper = UtopyaScraperSimple(
        cookies_file=args.cookies,
        output_file=args.output
    )
    
    # Exécution
    if args.test_product:
        scraper.run_test_product(args.test_product)
    elif args.test_category:
        scraper.run_test_category(args.test_category)
    elif args.full:
        scraper.run_full()
    else:
        parser.print_help()
        print("\nExemples :")
        print("  python3 utopya_scraper_simple.py --test-product https://www.utopya.fr/displayeinheit-iphone-15-pro-max-service-pack-862.html")
        print("  python3 utopya_scraper_simple.py --full")


if __name__ == "__main__":
    main()
