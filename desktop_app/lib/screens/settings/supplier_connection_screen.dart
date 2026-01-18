import 'dart:async';
import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import 'package:webview_flutter/webview_flutter.dart';
import 'package:webview_flutter_wkwebview/webview_flutter_wkwebview.dart';
import '../../services/auth_service.dart';
import '../../services/api_service.dart';
import '../../theme/macos_theme.dart';

class SupplierSettingsView extends StatefulWidget {
  const SupplierSettingsView({super.key});

  @override
  State<SupplierSettingsView> createState() => _SupplierSettingsViewState();
}

class _SupplierSettingsViewState extends State<SupplierSettingsView> {
  ApiService get _apiService => context.read<AuthService>().getApiService();
  
  bool _isLoading = true;
  List<dynamic> _suppliers = [];
  Map<int, bool> _authStatus = {}; // supplierId -> isConnected
  Map<int, DateTime?> _lastChecked = {}; // supplierId -> timestamp

  // Known Login URLs
  final Map<int, String> _supplierUrls = {
    2: 'https://www.utopya.fr/customer/account/login/', // Utopya
    11: 'https://www.mobilax.fr/login', // Mobilax
    17: 'https://www.wattiz.fr/connexion', // Wattiz
    18: 'https://www.lcd-phone.com/connexion', // LCD Phone
    19: 'https://jensmobiles.fr/mon-compte/', // JensMobiles
    20: 'https://mobilesentrix.eu/customer/account/login/', // MobileSentrix
  };

  @override
  void initState() {
    super.initState();
    _loadData();
  }

  Future<void> _loadData() async {
    setState(() => _isLoading = true);
    try {
      // Fetch Suppliers list and Auth status
      final suppliers = await _apiService.getSuppliers();
      final authData = await _apiService.getSupplierAuth();
      
      final connectedIds = List<int>.from(authData['connected_supplier_ids'] ?? []);
      final sessions = List<dynamic>.from(authData['sessions'] ?? []);

      if (mounted) {
        setState(() {
          _suppliers = suppliers;
          _authStatus.clear();
          
          // Initialise status
          for (var s in suppliers) {
            final id = int.tryParse(s['id'].toString()) ?? 0;
            if (connectedIds.contains(id)) {
              _authStatus[id] = true;
            } else {
              _authStatus[id] = false;
            }
          }
          
          // Last Checked times
          for (var sess in sessions) {
             final id = sess['supplier_id'];
             final dateStr = sess['last_checked'];
             if (dateStr != null) {
               _lastChecked[id] = DateTime.tryParse(dateStr);
             }
          }
        });
      }
    } catch (e) {
      if (mounted) {
         ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text('Erreur: $e'), backgroundColor: Colors.red));
      }
    } finally {
      if (mounted) setState(() => _isLoading = false);
    }
  }

  Future<void> _disconnect(int supplierId) async {
    try {
      await _apiService.updateSupplierAuth(supplierId, false);
      _loadData();
    } catch (e) {
      ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text('Erreur déconnexion: $e')));
    }
  }

  void _openLoginModal(int supplierId, String supplierName, String url) {
    showDialog(
      context: context,
      barrierDismissible: false,
      builder: (context) => _SupplierLoginModal(
        url: url,
        supplierName: supplierName,
        onSuccess: () async {
          // Capture messenger before popping/async gaps
          final messenger = ScaffoldMessenger.of(context);
          Navigator.of(context).pop();
          
          await _apiService.updateSupplierAuth(supplierId, true);
          _loadData(); // Refresh UI
          
          messenger.showSnackBar(
            SnackBar(content: Text('Connexion à $supplierName réussie !'), backgroundColor: Colors.green),
          );
        },
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    // ... no changes to build ...
    final isDark = Theme.of(context).brightness == Brightness.dark;

    return _isLoading 
        ? const Center(child: CircularProgressIndicator()) 
        : GridView.builder(
            shrinkWrap: true,
            physics: const NeverScrollableScrollPhysics(),
            padding: const EdgeInsets.all(0),
            gridDelegate: const SliverGridDelegateWithMaxCrossAxisExtent(
              maxCrossAxisExtent: 400,
              mainAxisSpacing: 16,
              crossAxisSpacing: 16,
              mainAxisExtent: 180,
            ),
            itemCount: _suppliers.length,
            itemBuilder: (context, index) {
              final s = _suppliers[index];
              final id = int.tryParse(s['id'].toString()) ?? 0;
              final name = s['nom'] ?? 'Inconnu';
              final isConnected = _authStatus[id] ?? false;
              final url = _supplierUrls[id] ?? s['url'];
              final lastCheck = _lastChecked[id];

              return Card(
                elevation: 2,
                color: isConnected 
                  ? (isDark ? Colors.green.withOpacity(0.1) : Colors.green.shade50)
                  : Theme.of(context).cardColor,
                shape: RoundedRectangleBorder(
                  borderRadius: BorderRadius.circular(12),
                  side: BorderSide(
                    color: isConnected ? Colors.green : (isDark ? Colors.white10 : Colors.grey.shade300),
                    width: isConnected ? 2 : 1
                  ),
                ),
                child: Padding(
                  padding: const EdgeInsets.all(16),
                  child: Column(
                    mainAxisAlignment: MainAxisAlignment.spaceBetween,
                    children: [
                      Row(
                        children: [
                          Icon(
                            isConnected ? Icons.check_circle : Icons.link_off,
                            color: isConnected ? Colors.green : Colors.grey,
                            size: 32,
                          ),
                          const SizedBox(width: 12),
                          Expanded(
                            child: Column(
                              crossAxisAlignment: CrossAxisAlignment.start,
                              children: [
                                Text(
                                  name,
                                  style: const TextStyle(fontSize: 18, fontWeight: FontWeight.bold),
                                  overflow: TextOverflow.ellipsis,
                                ),
                                Text(
                                  isConnected ? 'Connecté' : 'Non connecté',
                                  style: TextStyle(
                                    color: isConnected ? Colors.green : Colors.grey,
                                    fontSize: 12
                                  ),
                                ),
                              ],
                            ),
                          ),
                        ],
                      ),
                      
                      if (lastCheck != null && isConnected)
                        Text(
                          "Vérifié: ${lastCheck.day}/${lastCheck.month} à ${lastCheck.hour}h${lastCheck.minute}",
                          style: TextStyle(fontSize: 10, color: Colors.grey),
                        ),

                      SizedBox(
                        width: double.infinity,
                        child: ElevatedButton.icon(
                          icon: Icon(isConnected ? Icons.logout : Icons.login),
                          label: Text(isConnected ? "Déconnecter" : "Se connecter"),
                          style: ElevatedButton.styleFrom(
                            backgroundColor: isConnected ? Colors.red.withOpacity(0.8) : MacOSTheme.accentBlue,
                            foregroundColor: Colors.white,
                          ),
                          onPressed: () {
                            if (isConnected) {
                              _disconnect(id);
                            } else {
                              if (url != null) {
                                _openLoginModal(id, name, url);
                              } else {
                                ScaffoldMessenger.of(context).showSnackBar(
                                  const SnackBar(content: Text('URL de connexion inconnue pour ce fournisseur')),
                                );
                              }
                            }
                          },
                        ),
                      )
                    ],
                  ),
                ),
              );
            },
          );
  }
}

class _SupplierLoginModal extends StatefulWidget {
  final String url;
  final String supplierName;
  final VoidCallback onSuccess;

  const _SupplierLoginModal({
    required this.url,
    required this.supplierName,
    required this.onSuccess,
  });

  @override
  State<_SupplierLoginModal> createState() => _SupplierLoginModalState();
}

class _SupplierLoginModalState extends State<_SupplierLoginModal> {
  late final WebViewController _controller;
  bool _isLoading = true;
  double _progress = 0;

  @override
  void initState() {
    super.initState();

    late final PlatformWebViewControllerCreationParams params;
    if (WebViewPlatform.instance is WebKitWebViewPlatform) {
      params = WebKitWebViewControllerCreationParams(
        allowsInlineMediaPlayback: true,
      );
    } else {
      params = const PlatformWebViewControllerCreationParams();
    }

    final WebViewController controller =
        WebViewController.fromPlatformCreationParams(params);

    controller
      ..setJavaScriptMode(JavaScriptMode.unrestricted)
      ..setNavigationDelegate(
        NavigationDelegate(
          onProgress: (int progress) {
            if (mounted) setState(() => _progress = progress / 100);
          },
          onPageStarted: (String url) {
             if (mounted) setState(() => _isLoading = true);
          },
          onPageFinished: (String url) {
             if (mounted) setState(() => _isLoading = false);
             _injectLoginCheck();
          },
          onWebResourceError: (WebResourceError error) {},
          onNavigationRequest: (NavigationRequest request) {
            return NavigationDecision.navigate;
          },
        ),
      );

    _controller = controller;
    
    // Load URL after frame to avoid race conditions with Platform View creation
    WidgetsBinding.instance.addPostFrameCallback((_) async {
      // Clear Cache to force fresh login for testing
      await WebViewCookieManager().clearCookies();
      try {
         await _controller.clearLocalStorage();
      } catch (e) {
        // ignore
      }

      _controller.loadRequest(Uri.parse(widget.url));
      _startLoginCheckTimer();
    });
  }

  Timer? _checkTimer;

  void _startLoginCheckTimer() {
    _checkTimer?.cancel();
    _checkTimer = Timer.periodic(const Duration(seconds: 2), (timer) {
      if (!mounted) {
        timer.cancel();
        return;
      }
      _injectLoginCheck();
    });
  }

  @override
  void dispose() {
    _checkTimer?.cancel();
    super.dispose();
  }

  // Inject logic to detect login success (e.g. "My Account" link presence)
  void _injectLoginCheck() {
    // This logic mimics the extension's behavior. 
    // We check for common signs of being logged in.
    const checkScript = """
      (function() {
         const currentPath = window.location.pathname;
         const bodyText = document.body.innerText;
         
         // CRITICAL: If we are on a login page, we are NOT logged in.
         if (currentPath.includes('/login') || currentPath.includes('/connexion')) {
             return 'false';
         }

         // Pattern 1: 'account-link in' (Utopya style)
         const accountLinkIn = document.querySelector('.account-link.in');
         
         // Pattern 2: Avatar (Generic / Utopya)
         const hasAvatar = document.querySelector('.avatar');
         
         // Pattern 3: URL path contains /customer/account (Magento style)
         // BUT exclusion above handles the '/login' sub-case.
         const isAccountPage = currentPath.includes('/customer/account') || currentPath.includes('/account');

         // Pattern 4: Logout link presence (Strongest signal)
         const hasLogout = document.querySelector('a[href*="/customer/account/logout"]') 
                        || document.querySelector('a[href*="/logout"]')
                        || document.querySelector('a[href*="logout"]'); // Generic

         // Mobilax Specifics
         const mobilaxToken = localStorage.getItem('web_mobi_token');
         const hasValidMobilaxToken = mobilaxToken && mobilaxToken.length > 10;
         const hasMobilaxCagnotte = bodyText.includes("Ma cagnotte"); // Very specific to Mobilax dashboard
         
         // JensMobiles / WooCommerce
         const isWooCommerceAccount = document.body.classList.contains('logged-in') || currentPath.includes('/mon-compte');

         // Final Decision Logic
         if (hasLogout) return 'true'; // Explicit logout link is the best proof
         
         if (hasMobilaxCagnotte) return 'true'; // Visual proof for Mobilax
         
         if (hasValidMobilaxToken) return 'true'; // Mobilax token

         if (accountLinkIn || hasAvatar) return 'true'; // Utopya visual indicators

         if (isAccountPage && !currentPath.includes('/login')) return 'true'; // Account page but not login

         if (isWooCommerceAccount && !currentPath.includes('/connexion')) return 'true';

         return 'false';
      })();
    """;

    _controller.runJavaScriptReturningResult(checkScript).then((result) {
      if (result.toString() == 'true' || result.toString() == '"true"') {
        // Logged in!
        _checkTimer?.cancel(); // Stop checking once success is found
        widget.onSuccess();
      }
    }).catchError((e) {
      // Ignore errors during check (e.g. page loading)
    });
  }

  @override
  Widget build(BuildContext context) {
    return Dialog(
      insetPadding: const EdgeInsets.all(20),
      child: Container(
        width: 1000,
        height: 700,
        padding: const EdgeInsets.all(0),
        child: Column(
          children: [
            // Header
            Container(
              padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 12),
              color: Colors.grey.shade100,
              child: Row(
                mainAxisAlignment: MainAxisAlignment.spaceBetween,
                children: [
                  Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text("Connexion - ${widget.supplierName}", style: const TextStyle(fontWeight: FontWeight.bold, color: Colors.black)),
                      const Text("Connectez-vous pour autoriser l'affichage des prix", style: TextStyle(fontSize: 12, color: Colors.grey)),
                    ],
                  ),
                  IconButton(
                    icon: const Icon(Icons.close, color: Colors.black),
                    onPressed: () => Navigator.of(context).pop(),
                  )
                ],
              ),
            ),
            if (_isLoading || _progress < 1.0)
               LinearProgressIndicator(value: _progress),
               
            Expanded(
              child: WebViewWidget(
                key: UniqueKey(), // Force fresh PlatformView
                controller: _controller
              ),
            ),
          ],
        ),
      ),
    );
  }
}
