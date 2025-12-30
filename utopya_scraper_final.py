#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
Scraper Utopya.fr utilisant votre profil Chrome réel
Contourne les protections anti-bot en utilisant votre session déjà connectée
"""

import csv
import time
import random
import logging
import argparse
import os
from datetime import datetime
from selenium import webdriver
from selenium.webdriver.common.by import By
from selenium.webdriver.support.ui import WebDriverWait
from selenium.webdriver.support import expected_conditions as EC
from selenium.common.exceptions import TimeoutException, NoSuchElementException
from selenium.webdriver.chrome.service import Service
from selenium.webdriver.chrome.options import Options

# Configuration du logging
logging.basicConfig(
    level=logging.INFO,
    format='%(asctime)s - %(levelname)s - %(message)s',
    handlers=[
        logging.FileHandler('utopya_scraper_profile.log'),
        logging.StreamHandler()
    ]
)
logger = logging.getLogger(__name__)


class UtopyaScraperProfile:
    """Scraper utilisant le profil Chrome réel de l'utilisateur"""
    
    def __init__(self, profile_dir=None, output_file='utopya_catalogue.csv'):
        """
        Initialise le scraper avec le profil Chrome
        
        Args:
            profile_dir (str): Chemin du profil Chrome (None = profil par défaut)
            output_file (str): Nom du fichier CSV de sortie
        """
        self.base_url = "https://www.utopya.fr"
        self.output_file = output_file
        self.products_scraped = 0
        self.errors = 0
        
        # Profil Chrome par défaut sur Mac
        if profile_dir is None:
            profile_dir = os.path.expanduser("~/Library/Application Support/Google/Chrome/Default")
        
        logger.info(f"Utilisation du profil Chrome : {profile_dir}")
        
        if not os.path.exists(profile_dir):
            raise Exception(f"Profil Chrome introuvable : {profile_dir}")
        
        # Configuration de Chrome avec le profil utilisateur
        chrome_options = Options()
        chrome_options.add_argument(f"user-data-dir={os.path.dirname(profile_dir)}")
        chrome_options.add_argument(f"profile-directory={os.path.basename(profile_dir)}")
        chrome_options.add_argument("--no-sandbox")
        chrome_options.add_argument("--disable-dev-shm-usage")
        chrome_options.add_argument("--disable-blink-features=AutomationControlled")
        chrome_options.add_experimental_option("excludeSwitches", ["enable-automation"])
        chrome_options.add_experimental_option('useAutomationExtension', False)
        
        # Initialisation du driver
        logger.info("Initialisation de Chrome avec votre profil...")
        logger.info("⚠️  Fermez Chrome si il est déjà ouvert !")
        time.sleep(2)
        
        try:
            self.driver = webdriver.Chrome(options=chrome_options)
        except Exception as e:
            logger.error(f"Erreur lors de l'initialisation de Chrome: {e}")
            logger.error("Assurez-vous que Chrome est fermé avant de lancer le scraper !")
            raise
        
        self.wait = WebDriverWait(self.driver, 15)
        
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
        """Pause aléatoire"""
        delay = random.uniform(min_seconds, max_seconds)
        time.sleep(delay)
    
    def verify_logged_in(self):
        """
        Vérifie que l'utilisateur est connecté sur Utopya
        
        Returns:
            bool: True si connecté
        """
        logger.info("Vérification de la connexion...")
        self.driver.get(self.base_url)
        time.sleep(3)
        
        # Vérifie la présence d'un indicateur de connexion
        try:
            # Cherche un élément qui indique qu'on est connecté
            self.wait.until(
                EC.presence_of_element_located((By.CSS_SELECTOR, "nav, .page-wrapper"))
            )
            
            current_url = self.driver.current_url
            if "customer/account/login" in current_url:
                logger.error("❌ Vous n'êtes pas connecté!")
                logger.error("Veuillez vous connecter manuellement dans le navigateur qui vient de s'ouvrir,")
                logger.error("puis appuyez sur ENTRÉE pour continuer...")
                input("\nAppuyez sur ENTRÉE après vous être connecté...\n")
            else:
                logger.info("✓ Session active détectée")
            
            return True
        except Exception as e:
            logger.error(f"Erreur lors de la vérification : {e}")
            return False
    
    def get_all_categories(self):
        """Extrait toutes les URLs de catégories"""
        logger.info("Extraction des catégories...")
        self.driver.get(self.base_url)
        self._random_delay(2, 3)
        
        categories = []
        
        try:
            nav_links = self.driver.find_elements(By.CSS_SELECTOR, "nav a[href*='.html']")
            
            for link in nav_links:
                url = link.get_attribute('href')
                if url and url.startswith(self.base_url) and url != self.base_url + '/' and url not in categories:
                    categories.append(url)
                    logger.info(f"Catégorie : {url}")
                    
        except Exception as e:
            logger.error(f"Erreur : {e}")
        
        logger.info(f"Total : {len(categories)} catégories")
        return categories
    
    def get_products_from_category(self, category_url, max_pages=None):
        """Extrait les URLs de produits d'une catégorie"""
        logger.info(f"Catégorie : {category_url}")
        products = []
        page = 1
        
        while True:
            if max_pages and page > max_pages:
                break
            
            url = f"{category_url}?p={page}" if page > 1 else category_url
            
            logger.info(f"Page {page}...")
            self.driver.get(url)
            self._random_delay()
            
            try:
                self.wait.until(EC.presence_of_element_located((By.CSS_SELECTOR, "li.item.product")))
                
                product_links = self.driver.find_elements(By.CSS_SELECTOR, "li.item.product a.product-item-link")
                
                if not product_links:
                    break
                
                page_products = []
                for link in product_links:
                    product_url = link.get_attribute('href')
                    if product_url and product_url not in products:
                        products.append(product_url)
                        page_products.append(product_url)
                
                logger.info(f"  → {len(page_products)} produits")
                
                # Vérifie la pagination
                next_button = self.driver.find_elements(By.CSS_SELECTOR, ".pages-items .action.next")
                if not next_button or not next_button[0].is_displayed():
                    break
                
                page += 1
                
            except TimeoutException:
                break
            except Exception as e:
                logger.error(f"Erreur page {page} : {e}")
                break
        
        logger.info(f"Total : {len(products)} produits")
        return products
    
    def scrape_product(self, product_url):
        """Extrait les données d'un produit"""
        logger.info(f"Produit : {product_url}")
        
        try:
            self.driver.get(product_url)
            self._random_delay(2, 3)
            
            self.wait.until(EC.presence_of_element_located((By.CSS_SELECTOR, "h1.page-title")))
            
            product_data = {
                'PRODUIT': '',
                'PRIX': '',
                'URL': product_url,
                'REFERENCE': '',
                'SKU': '',
                'DATE_EXTRACTION': datetime.now().strftime('%Y-%m-%d %H:%M:%S')
            }
            
            # Nom
            try:
                name_elem = self.driver.find_element(By.CSS_SELECTOR, "h1.page-title span.base")
                product_data['PRODUIT'] = name_elem.text.strip()
            except:
                pass
            
            # Prix
            try:
                price_elem = self.driver.find_element(By.CSS_SELECTOR, ".product-info-main .price-wrapper .price")
                product_data['PRIX'] = price_elem.text.strip()
            except:
                product_data['PRIX'] = 'N/A'
            
            # SKU
            try:
                sku_elem = self.driver.find_element(By.CSS_SELECTOR, ".product-info-main .sku .value")
                product_data['SKU'] = sku_elem.text.strip()
            except:
                pass
            
            # Référence
            try:
                attributes = self.driver.find_elements(By.CSS_SELECTOR, ".product-info-main .additional-attributes tr")
                for attr in attributes:
                    try:
                        label = attr.find_element(By.CSS_SELECTOR, "th").text.strip().lower()
                        if 'référence' in label or 'reference' in label or 'fabricant' in label:
                            value = attr.find_element(By.CSS_SELECTOR, "td").text.strip()
                            product_data['REFERENCE'] = value
                            break
                    except:
                        continue
            except:
                pass
            
            logger.info(f"✓ {product_data['PRODUIT']}")
            return product_data
            
        except Exception as e:
            logger.error(f"Erreur : {e}")
            self.errors += 1
            return None
    
    def save_to_csv(self, product_data):
        """Sauvegarde dans le CSV"""
        if not product_data:
            return
        
        try:
            with open(self.output_file, 'a', newline='', encoding='utf-8') as f:
                writer = csv.DictWriter(f, fieldnames=['PRODUIT', 'PRIX', 'URL', 'REFERENCE', 'SKU', 'DATE_EXTRACTION'])
                writer.writerow(product_data)
            self.products_scraped += 1
            logger.info(f"→ Sauvegardé (total: {self.products_scraped})")
        except Exception as e:
            logger.error(f"Erreur sauvegarde : {e}")
    
    def run_full(self):
        """Scraping complet"""
        logger.info("=" * 80)
        logger.info("SCRAPING COMPLET DU CATALOGUE UTOPYA.FR")
        logger.info("=" * 80)
        
        # Vérification de la connexion
        if not self.verify_logged_in():
            logger.error("Impossible de continuer sans connexion")
            self.close()
            return
        
        start_time = time.time()
        
        try:
            # Catégories
            categories = self.get_all_categories()
            
            if not categories:
                logger.error("Aucune catégorie trouvée")
                return
            
            # Produits
            all_products = []
            for i, category_url in enumerate(categories, 1):
                logger.info(f"\n[{i}/{len(categories)}] {category_url}")
                products = self.get_products_from_category(category_url)
                all_products.extend(products)
            
            # Scraping
            logger.info(f"\n{'=' * 80}")
            logger.info(f"SCRAPING DE {len(all_products)} PRODUITS")
            logger.info(f"{'=' * 80}\n")
            
            for i, product_url in enumerate(all_products, 1):
                logger.info(f"\n[{i}/{len(all_products)}]")
                product_data = self.scrape_product(product_url)
                if product_data:
                    self.save_to_csv(product_data)
                
                if i % 50 == 0:
                    logger.info(f"\nCHECKPOINT : {i} traités, {self.products_scraped} sauvegardés\n")
        
        except KeyboardInterrupt:
            logger.warning("\nInterruption utilisateur")
        except Exception as e:
            logger.error(f"Erreur fatale : {e}")
        finally:
            self.close()
        
        # Stats
        elapsed_time = time.time() - start_time
        logger.info(f"\n{'=' * 80}")
        logger.info(f"Produits scrapés : {self.products_scraped}")
        logger.info(f"Erreurs : {self.errors}")
        logger.info(f"Temps : {elapsed_time/60:.2f} minutes")
        logger.info(f"Fichier : {self.output_file}")
        logger.info(f"{'=' * 80}\n")
    
    def run_test_product(self, product_url):
        """Test sur un produit"""
        logger.info("TEST : Un seul produit")
        
        if not self.verify_logged_in():
            self.close()
            return
        
        product_data = self.scrape_product(product_url)
        if product_data:
            self.save_to_csv(product_data)
            logger.info("\nDonnées :")
            for key, value in product_data.items():
                logger.info(f"  {key}: {value}")
        self.close()
    
    def close(self):
        """Ferme le navigateur"""
        if self.driver:
            self.driver.quit()
            logger.info("Navigateur fermé")


def main():
    parser = argparse.ArgumentParser(description='Scraper Utopya.fr avec profil Chrome')
    parser.add_argument('--full', action='store_true', help='Scraping complet')
    parser.add_argument('--test-product', type=str, help='Test sur un produit')
    parser.add_argument('--profile-dir', type=str, help='Chemin du profil Chrome (optionnel)')
    parser.add_argument('--output', type=str, default='utopya_catalogue.csv', help='Fichier CSV')
    
    args = parser.parse_args()
    
    logger.info("\n🚨 IMPORTANT : Fermez Chrome avant de continuer !")
    logger.info("Le scraper doit utiliser votre profil Chrome, qui ne peut être ouvert qu'une seule fois.\n")
    input("Appuyez sur ENTRÉE quand Chrome est fermé...")
    
    # Initialisation
    scraper = UtopyaScraperProfile(
        profile_dir=args.profile_dir,
        output_file=args.output
    )
    
    # Exécution
    if args.test_product:
        scraper.run_test_product(args.test_product)
    elif args.full:
        scraper.run_full()
    else:
        parser.print_help()


if __name__ == "__main__":
    main()
