#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
Scraper pour le catalogue Utopya.fr
Extrait : PRODUIT, PRIX, URL, REFERENCE, SKU
"""

import csv
import time
import random
import logging
import argparse
import os
from datetime import datetime
from dotenv import load_dotenv
from selenium import webdriver
from selenium.webdriver.common.by import By
from selenium.webdriver.support.ui import WebDriverWait
from selenium.webdriver.support import expected_conditions as EC
from selenium.common.exceptions import TimeoutException, NoSuchElementException
from webdriver_manager.chrome import ChromeDriverManager
from selenium.webdriver.chrome.service import Service
from selenium.webdriver.chrome.options import Options

# Configuration du logging
logging.basicConfig(
    level=logging.INFO,
    format='%(asctime)s - %(levelname)s - %(message)s',
    handlers=[
        logging.FileHandler('utopya_scraper.log'),
        logging.StreamHandler()
    ]
)
logger = logging.getLogger(__name__)


class UtopyaScraper:
    """Scraper pour extraire le catalogue complet de Utopya.fr"""
    
    def __init__(self, headless=True, output_file='utopya_catalogue.csv', manual_login=False):
        """
        Initialise le scraper avec Selenium
        
        Args:
            headless (bool): Mode headless pour Chrome
            output_file (str): Nom du fichier CSV de sortie
            manual_login (bool): Si True, permet la connexion manuelle dans le navigateur
        """
        self.manual_login = manual_login
        # Chargement des credentials
        load_dotenv('.env.utopya')
        self.email = os.getenv('UTOPYA_EMAIL')
        self.password = os.getenv('UTOPYA_PASSWORD')
        
        if not self.email or not self.password:
            raise Exception("Credentials manquants ! Vérifiez le fichier .env.utopya")
        
        self.base_url = "https://www.utopya.fr"
        self.output_file = output_file
        self.products_scraped = 0
        self.errors = 0
        self.logged_in = False
        
        # Configuration de Chrome
        chrome_options = Options()
        if headless:
            chrome_options.add_argument("--headless")
        chrome_options.add_argument("--no-sandbox")
        chrome_options.add_argument("--disable-dev-shm-usage")
        chrome_options.add_argument("--disable-blink-features=AutomationControlled")
        chrome_options.add_argument("user-agent=Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36")
        
        # Initialisation du driver
        logger.info("Initialisation du navigateur Chrome...")
        try:
            service = Service(ChromeDriverManager().install())
            self.driver = webdriver.Chrome(service=service, options=chrome_options)
        except Exception as e:
            logger.error(f"Erreur lors de l'initialisation de Chrome avec webdriver-manager: {e}")
            logger.info("Tentative avec ChromeDriver système...")
            try:
                # Essaie avec le ChromeDriver système
                self.driver = webdriver.Chrome(options=chrome_options)
            except Exception as e2:
                logger.error(f"Impossible d'initialiser Chrome: {e2}")
                raise Exception("Impossible de démarrer Chrome. Assurez-vous que Chrome et ChromeDriver sont installés.")
        
        self.wait = WebDriverWait(self.driver, 10)
        
        # Initialisation du fichier CSV
        self._init_csv()
        
    def _init_csv(self):
        """Initialise le fichier CSV avec les en-têtes"""
        try:
            # Vérifie si le fichier existe déjà
            with open(self.output_file, 'r', encoding='utf-8') as f:
                logger.info(f"Fichier CSV existant trouvé : {self.output_file}")
                logger.info("Les nouveaux produits seront ajoutés à la suite")
        except FileNotFoundError:
            # Crée le fichier avec en-têtes
            with open(self.output_file, 'w', newline='', encoding='utf-8') as f:
                writer = csv.DictWriter(f, fieldnames=['PRODUIT', 'PRIX', 'URL', 'REFERENCE', 'SKU', 'DATE_EXTRACTION'])
                writer.writeheader()
            logger.info(f"Nouveau fichier CSV créé : {self.output_file}")
    
    def _random_delay(self, min_seconds=2, max_seconds=4):
        """Pause aléatoire pour éviter le bannissement"""
        delay = random.uniform(min_seconds, max_seconds)
        time.sleep(delay)
    
    def login(self):
        """
        Se connecte au site Utopya.fr avec les credentials
        
        Returns:
            bool: True si la connexion est réussie, False sinon
        """
        if self.logged_in:
            logger.info("Déjà connecté")
            return True
        
        logger.info("Connexion au site Utopya.fr...")
        
        try:
            # Va sur la page de connexion
            self.driver.get(f"{self.base_url}/customer/account/login/")
            self._random_delay(2, 3)
            
            # Remplit le formulaire de connexion
            try:
                email_field = self.wait.until(
                    EC.presence_of_element_located((By.CSS_SELECTOR, "input[name='login[username]'], input#email"))
                )
                password_field = self.driver.find_element(By.CSS_SELECTOR, "input[name='login[password]'], input#pass")
                
                email_field.clear()
                email_field.send_keys(self.email)
                
                password_field.clear()
                password_field.send_keys(self.password)
                
                logger.info("Credentials entrés, soumission du formulaire...")
                
                # Soumet le formulaire
                login_button = self.driver.find_element(By.CSS_SELECTOR, "button[type='submit'].action.login")
                login_button.click()
                
                # Attend la redirection après login
                time.sleep(5)
                
                # Vérifie si la connexion est réussie
                current_url = self.driver.current_url
                
                # Si on est encore sur la page de login, c'est que ça a échoué
                if "customer/account/login" in current_url:
                    logger.error("Échec de la connexion - Toujours sur la page de login")
                    # Capture une erreur éventuelle
                    try:
                        error_msg = self.driver.find_element(By.CSS_SELECTOR, ".message-error, .messages .error").text
                        logger.error(f"Message d'erreur : {error_msg}")
                    except:
                        pass
                    return False
                
                # Vérifie la présence d'un élément qui indique qu'on est connecté
                try:
                    self.wait.until(
                        EC.presence_of_element_located((By.CSS_SELECTOR, ".customer-name, .customer-welcome"))
                    )
                    logger.info("✓ Connexion réussie !")
                    self.logged_in = True
                    return True
                except TimeoutException:
                    logger.warning("Timeout lors de la vérification de connexion, on suppose que c'est OK")
                    self.logged_in = True
                    return True
                    
            except NoSuchElementException as e:
                logger.error(f"Impossible de trouver les champs de connexion : {e}")
                return False
                
        except Exception as e:
            logger.error(f"Erreur lors de la connexion : {e}")
            return False
    
    def login_manual(self):
        """
        Permet à l'utilisateur de se connecter manuellement via le navigateur
        
        Returns:
            bool: True si l'utilisateur confirme être connecté
        """
        logger.info("=" * 80)
        logger.info("MODE CONNEXION MANUELLE")
        logger.info("=" * 80)
        logger.info("Le navigateur va s'ouvrir sur la page de connexion d'Utopya.")
        logger.info("Veuillez vous connecter manuellement, puis appuyez sur ENTRÉE pour continuer...")
        logger.info("=" * 80)
        
        # Ouvre la page de connexion
        self.driver.get(f"{self.base_url}/customer/account/login/")
        
        # Attend que l'utilisateur se connecte et appuie sur Entrée
        input("\n🔐 Connectez-vous dans le navigateur, puis appuyez sur ENTRÉE pour continuer le scraping...\n")
        
        # Vérifie si l'utilisateur est connecté
        current_url = self.driver.current_url
        if "customer/account/login" not in current_url:
            logger.info("✓ Connexion détectée, démarrage du scraping...")
            self.logged_in = True
            return True
        else:
            logger.warning("Il semble que vous soyez toujours sur la page de connexion.")
            response = input("Voulez-vous continuer quand même ? (o/n) : ")
            if response.lower() in ['o', 'oui', 'y', 'yes']:
                self.logged_in = True
                return True
            return False
    
    def get_all_categories(self):
        """
        Extrait toutes les URLs de catégories depuis la page d'accueil
        
        Returns:
            list: Liste des URLs de catégories
        """
        logger.info("Extraction des catégories depuis la page d'accueil...")
        self.driver.get(self.base_url)
        self._random_delay()
        
        categories = []
        
        try:
            # Cherche tous les liens de navigation dans le menu principal
            nav_links = self.driver.find_elements(By.CSS_SELECTOR, "nav .nav-sections a.level-top, nav .nav-sections a[href*='/']")
            
            for link in nav_links:
                url = link.get_attribute('href')
                if url and url.startswith(self.base_url) and url != self.base_url + '/' and '.html' in url:
                    if url not in categories:
                        categories.append(url)
                        logger.info(f"Catégorie trouvée : {url}")
            
            # Cherche aussi les sous-catégories dans les menus déroulants
            submenu_links = self.driver.find_elements(By.CSS_SELECTOR, "nav .submenu a[href*='.html']")
            for link in submenu_links:
                url = link.get_attribute('href')
                if url and url not in categories:
                    categories.append(url)
                    logger.info(f"Sous-catégorie trouvée : {url}")
                    
        except Exception as e:
            logger.error(f"Erreur lors de l'extraction des catégories : {e}")
        
        logger.info(f"Total de {len(categories)} catégories trouvées")
        return categories
    
    def get_products_from_category(self, category_url, max_pages=None):
        """
        Extrait toutes les URLs de produits depuis une catégorie
        
        Args:
            category_url (str): URL de la catégorie
            max_pages (int): Nombre maximum de pages à scraper (None = toutes)
            
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
                # Magento utilise ?p=X pour la pagination
                url = f"{category_url}?p={page}"
            
            logger.info(f"Scraping page {page} : {url}")
            self.driver.get(url)
            self._random_delay()
            
            try:
                # Attend que les produits soient chargés
                self.wait.until(EC.presence_of_element_located((By.CSS_SELECTOR, "li.item.product")))
                
                # Extrait tous les liens produits
                product_links = self.driver.find_elements(By.CSS_SELECTOR, "li.item.product a.product-item-link")
                
                if not product_links:
                    logger.info("Aucun produit trouvé sur cette page, fin de la pagination")
                    break
                
                page_products = []
                for link in product_links:
                    product_url = link.get_attribute('href')
                    if product_url and product_url not in products:
                        products.append(product_url)
                        page_products.append(product_url)
                
                logger.info(f"  → {len(page_products)} produits trouvés sur cette page")
                
                # Vérifie s'il y a une page suivante
                try:
                    next_button = self.driver.find_elements(By.CSS_SELECTOR, ".pages-items .action.next")
                    if not next_button or not next_button[0].is_displayed():
                        logger.info("Dernière page atteinte")
                        break
                except:
                    logger.info("Pas de bouton suivant, fin de la pagination")
                    break
                
                page += 1
                
            except TimeoutException:
                logger.warning(f"Timeout lors du chargement de la page {page}")
                break
            except Exception as e:
                logger.error(f"Erreur lors de l'extraction de la page {page} : {e}")
                break
        
        logger.info(f"Total de {len(products)} produits trouvés dans cette catégorie")
        return products
    
    def scrape_product(self, product_url):
        """
        Extrait les données d'un produit
        
        Args:
            product_url (str): URL du produit
            
        Returns:
            dict: Données du produit ou None en cas d'erreur
        """
        logger.info(f"Scraping produit : {product_url}")
        
        try:
            self.driver.get(product_url)
            self._random_delay()
            
            # Attend que la page soit chargée
            self.wait.until(EC.presence_of_element_located((By.CSS_SELECTOR, "h1.page-title")))
            
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
                name_element = self.driver.find_element(By.CSS_SELECTOR, "h1.page-title span.base")
                product_data['PRODUIT'] = name_element.text.strip()
            except NoSuchElementException:
                logger.warning("Nom du produit non trouvé")
            
            # Extraction du prix
            try:
                price_element = self.driver.find_element(By.CSS_SELECTOR, ".product-info-main .price-wrapper .price")
                price_text = price_element.text.strip()
                product_data['PRIX'] = price_text
            except NoSuchElementException:
                logger.warning("Prix non trouvé (produit peut-être en rupture)")
                product_data['PRIX'] = 'N/A'
            
            # Extraction du SKU
            try:
                sku_element = self.driver.find_element(By.CSS_SELECTOR, ".product-info-main .sku .value")
                product_data['SKU'] = sku_element.text.strip()
            except NoSuchElementException:
                logger.warning("SKU non trouvé")
            
            # Extraction de la référence fabricant
            try:
                # Cherche dans les attributs techniques
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
                
                # Si pas trouvé, cherche dans une autre section
                if not product_data['REFERENCE']:
                    ref_elements = self.driver.find_elements(By.XPATH, "//*[contains(text(), 'Référence') or contains(text(), 'référence')]")
                    for elem in ref_elements:
                        parent = elem.find_element(By.XPATH, "..")
                        text = parent.text
                        if ':' in text:
                            product_data['REFERENCE'] = text.split(':')[-1].strip()
                            break
                            
            except Exception as e:
                logger.warning(f"Référence non trouvée : {e}")
            
            logger.info(f"✓ Produit extrait : {product_data['PRODUIT']}")
            return product_data
            
        except Exception as e:
            logger.error(f"Erreur lors du scraping du produit {product_url} : {e}")
            self.errors += 1
            return None
    
    def save_to_csv(self, product_data):
        """
        Sauvegarde les données d'un produit dans le CSV
        
        Args:
            product_data (dict): Données du produit
        """
        if not product_data:
            return
            
        try:
            with open(self.output_file, 'a', newline='', encoding='utf-8') as f:
                writer = csv.DictWriter(f, fieldnames=['PRODUIT', 'PRIX', 'URL', 'REFERENCE', 'SKU', 'DATE_EXTRACTION'])
                writer.writerow(product_data)
            self.products_scraped += 1
            logger.info(f"→ Produit sauvegardé dans {self.output_file} (total: {self.products_scraped})")
        except Exception as e:
            logger.error(f"Erreur lors de la sauvegarde : {e}")
    
    def run_full(self):
        """Lance le scraping complet du catalogue"""
        logger.info("=" * 80)
        logger.info("DÉMARRAGE DU SCRAPING COMPLET DU CATALOGUE UTOPYA.FR")
        logger.info("=" * 80)
        
        # Connexion obligatoire
        if self.manual_login:
            if not self.login_manual():
                logger.error("Connexion annulée par l'utilisateur")
                self.close()
                return
        else:
            if not self.login():
                logger.error("Impossible de se connecter au site, arrêt du scraping")
                self.close()
                return
        
        start_time = time.time()
        
        try:
            # 1. Extraction des catégories
            categories = self.get_all_categories()
            
            if not categories:
                logger.error("Aucune catégorie trouvée, arrêt du scraping")
                return
            
            # 2. Pour chaque catégorie, extraire les produits
            all_products = []
            for i, category_url in enumerate(categories, 1):
                logger.info(f"\n[{i}/{len(categories)}] Traitement de la catégorie : {category_url}")
                products = self.get_products_from_category(category_url)
                all_products.extend(products)
                logger.info(f"Progression : {len(all_products)} produits trouvés au total")
            
            # 3. Scraper chaque produit
            logger.info(f"\n{'=' * 80}")
            logger.info(f"SCRAPING DE {len(all_products)} PRODUITS")
            logger.info(f"{'=' * 80}\n")
            
            for i, product_url in enumerate(all_products, 1):
                logger.info(f"\n[{i}/{len(all_products)}] Produit {i}")
                product_data = self.scrape_product(product_url)
                if product_data:
                    self.save_to_csv(product_data)
                
                # Checkpoint tous les 50 produits
                if i % 50 == 0:
                    logger.info(f"\n{'=' * 80}")
                    logger.info(f"CHECKPOINT : {i} produits traités, {self.products_scraped} sauvegardés")
                    logger.info(f"{'=' * 80}\n")
            
        except KeyboardInterrupt:
            logger.warning("\n\nInterruption par l'utilisateur (Ctrl+C)")
        except Exception as e:
            logger.error(f"Erreur fatale : {e}")
        finally:
            self.close()
            
        # Statistiques finales
        elapsed_time = time.time() - start_time
        logger.info(f"\n{'=' * 80}")
        logger.info("SCRAPING TERMINÉ")
        logger.info(f"{'=' * 80}")
        logger.info(f"Produits scrapés : {self.products_scraped}")
        logger.info(f"Erreurs : {self.errors}")
        logger.info(f"Temps écoulé : {elapsed_time/60:.2f} minutes")
        logger.info(f"Fichier de sortie : {self.output_file}")
        logger.info(f"{'=' * 80}\n")
    
    def run_test_product(self, product_url):
        """
        Test sur un seul produit
        
        Args:
            product_url (str): URL du produit à tester
        """
        logger.info(f"TEST : Scraping d'un seul produit")
        
        # Connexion obligatoire
        if self.manual_login:
            if not self.login_manual():
                logger.error("Connexion annulée par l'utilisateur")
                self.close()
                return
        else:
            if not self.login():
                logger.error("Impossible de se connecter au site")
                self.close()
                return
        product_data = self.scrape_product(product_url)
        if product_data:
            self.save_to_csv(product_data)
            logger.info(f"\nDonnées extraites :")
            for key, value in product_data.items():
                logger.info(f"  {key}: {value}")
        self.close()
    
    def run_test_category(self, category_url, max_products=10):
        """
        Test sur une catégorie
        
        Args:
            category_url (str): URL de la catégorie
            max_products (int): Nombre maximum de produits à scraper
        """
        logger.info(f"TEST : Scraping d'une catégorie (max {max_products} produits)")
        
        # Connexion obligatoire
        if self.manual_login:
            if not self.login_manual():
                logger.error("Connexion annulée par l'utilisateur")
                self.close()
                return
        else:
            if not self.login():
                logger.error("Impossible de se connecter au site")
                self.close()
                return
        products = self.get_products_from_category(category_url, max_pages=2)
        
        products_to_scrape = products[:max_products]
        logger.info(f"\nScraping de {len(products_to_scrape)} produits...")
        
        for i, product_url in enumerate(products_to_scrape, 1):
            logger.info(f"\n[{i}/{len(products_to_scrape)}]")
            product_data = self.scrape_product(product_url)
            if product_data:
                self.save_to_csv(product_data)
        
        self.close()
        logger.info(f"\nTest terminé : {self.products_scraped} produits sauvegardés dans {self.output_file}")
    
    def close(self):
        """Ferme le navigateur"""
        if self.driver:
            self.driver.quit()
            logger.info("Navigateur fermé")


def main():
    """Point d'entrée du script"""
    parser = argparse.ArgumentParser(description='Scraper pour le catalogue Utopya.fr')
    parser.add_argument('--full', action='store_true', help='Lance le scraping complet du catalogue')
    parser.add_argument('--test-product', type=str, help='Test sur un seul produit (URL)')
    parser.add_argument('--test-category', type=str, help='Test sur une catégorie (URL)')
    parser.add_argument('--output', type=str, default='utopya_catalogue.csv', help='Fichier CSV de sortie')
    parser.add_argument('--visible', action='store_true', help='Mode visible (pas headless)')
    parser.add_argument('--manual-login', action='store_true', help='Connexion manuelle dans le navigateur (mode visible forcé)')
    
    args = parser.parse_args()
    
    # Si manual_login, forcer le mode visible
    if args.manual_login:
        args.visible = True
    
    # Initialisation du scraper
    scraper = UtopyaScraper(
        headless=not args.visible,
        output_file=args.output,
        manual_login=args.manual_login
    )
    
    # Exécution selon le mode
    if args.test_product:
        scraper.run_test_product(args.test_product)
    elif args.test_category:
        scraper.run_test_category(args.test_category)
    elif args.full:
        scraper.run_full()
    else:
        parser.print_help()
        print("\nExemples d'utilisation :")
        print("  python3 utopya_scraper.py --test-product https://www.utopya.fr/displayeinheit-iphone-15-pro-max-service-pack-862.html")
        print("  python3 utopya_scraper.py --test-category https://www.utopya.fr/apple/iphone.html")
        print("  python3 utopya_scraper.py --full")
        scraper.close()


if __name__ == "__main__":
    main()
