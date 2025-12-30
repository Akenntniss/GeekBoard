/// Dashboard Screen - Écran principal avec statistiques
import 'package:flutter/material.dart';
import 'package:google_fonts/google_fonts.dart';
import 'package:provider/provider.dart';
import 'package:intl/intl.dart';
import '../services/auth_service.dart';
import '../widgets/sidebar.dart';
import '../widgets/stat_card.dart';
import '../models/reparation.dart';

class DashboardScreen extends StatefulWidget {
  const DashboardScreen({super.key});

  @override
  State<DashboardScreen> createState() => _DashboardScreenState();
}

class _DashboardScreenState extends State<DashboardScreen> {
  Map<String, dynamic> _stats = {};
  List<Reparation> _recentReparations = [];
  bool _isLoading = true;
  String? _error;

  @override
  void initState() {
    super.initState();
    _loadDashboardData();
  }

  Future<void> _loadDashboardData() async {
    setState(() {
      _isLoading = true;
      _error = null;
    });

    try {
      final authService = context.read<AuthService>();
      final apiService = authService.getApiService();
      final data = await apiService.getDashboardStats();

      setState(() {
        _stats = data;
        _recentReparations = (data['reparations_recentes'] as List?)
                ?.map((json) => Reparation.fromJson(json))
                .toList() ??
            [];
        _isLoading = false;
      });
    } catch (e) {
      setState(() {
        _error = e.toString();
        _isLoading = false;
      });
    }
  }

  @override
  Widget build(BuildContext context) {
    final authService = context.watch<AuthService>();
    final currencyFormat = NumberFormat.currency(locale: 'fr_FR', symbol: '€');

    return Scaffold(
      body: Row(
        children: [
          // Sidebar
          const Sidebar(currentRoute: '/dashboard'),

          // Contenu principal
          Expanded(
            child: Container(
              color: const Color(0xFFF5F7FA),
              child: Column(
                children: [
                  // Header
                  Container(
                    padding: const EdgeInsets.all(24),
                    decoration: BoxDecoration(
                      color: Colors.white,
                      boxShadow: [
                        BoxShadow(
                          color: Colors.black.withValues(alpha: 0.05),
                          blurRadius: 10,
                          offset: const Offset(0, 2),
                        ),
                      ],
                    ),
                    child: Row(
                      children: [
                        Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            Text(
                              'Tableau de bord',
                              style: GoogleFonts.poppins(
                                fontSize: 28,
                                fontWeight: FontWeight.bold,
                                color: const Color(0xFF1a1a2e),
                              ),
                            ),
                            Text(
                              authService.currentShop?.name ?? '',
                              style: GoogleFonts.poppins(
                                fontSize: 14,
                                color: Colors.grey[600],
                              ),
                            ),
                          ],
                        ),
                        const Spacer(),
                        IconButton(
                          icon: const Icon(Icons.refresh),
                          onPressed: _loadDashboardData,
                          tooltip: 'Actualiser',
                        ),
                        const SizedBox(width: 16),
                        CircleAvatar(
                          backgroundColor: const Color(0xFF667eea),
                          child: Text(
                            (authService.currentUser?.prenom.isNotEmpty == true
                                    ? authService.currentUser!.prenom[0]
                                    : 'U')
                                .toUpperCase(),
                            style: const TextStyle(color: Colors.white),
                          ),
                        ),
                        const SizedBox(width: 8),
                        Text(
                          authService.currentUser?.fullName ?? 'Utilisateur',
                          style: GoogleFonts.poppins(
                            fontWeight: FontWeight.w500,
                          ),
                        ),
                      ],
                    ),
                  ),

                  // Contenu
                  Expanded(
                    child: _isLoading
                        ? const Center(child: CircularProgressIndicator())
                        : _error != null
                            ? Center(
                                child: Column(
                                  mainAxisSize: MainAxisSize.min,
                                  children: [
                                    Icon(Icons.error_outline,
                                        size: 64, color: Colors.red[300]),
                                    const SizedBox(height: 16),
                                    Text(_error!),
                                    const SizedBox(height: 16),
                                    ElevatedButton(
                                      onPressed: _loadDashboardData,
                                      child: const Text('Réessayer'),
                                    ),
                                  ],
                                ),
                              )
                            : SingleChildScrollView(
                                padding: const EdgeInsets.all(24),
                                child: Column(
                                  crossAxisAlignment: CrossAxisAlignment.start,
                                  children: [
                                    // Cartes statistiques
                                    Wrap(
                                      spacing: 20,
                                      runSpacing: 20,
                                      children: [
                                        StatCard(
                                          title: 'Réparations en cours',
                                          value: (_stats['reparations']
                                                      ?['en_cours'] ??
                                                  0)
                                              .toString(),
                                          icon: Icons.build,
                                          color: const Color(0xFF667eea),
                                        ),
                                        StatCard(
                                          title: 'En attente',
                                          value: (_stats['reparations']
                                                      ?['en_attente'] ??
                                                  0)
                                              .toString(),
                                          icon: Icons.hourglass_empty,
                                          color: const Color(0xFFf093fb),
                                        ),
                                        StatCard(
                                          title: 'CA du jour',
                                          value: currencyFormat
                                              .format(_stats['ca_jour'] ?? 0),
                                          icon: Icons.euro,
                                          color: const Color(0xFF4facfe),
                                        ),
                                        StatCard(
                                          title: 'CA du mois',
                                          value: currencyFormat
                                              .format(_stats['ca_mois'] ?? 0),
                                          icon: Icons.trending_up,
                                          color: const Color(0xFF43e97b),
                                        ),
                                        StatCard(
                                          title: 'Total clients',
                                          value: (_stats['clients_total'] ?? 0)
                                              .toString(),
                                          icon: Icons.people,
                                          color: const Color(0xFFfa709a),
                                        ),
                                        StatCard(
                                          title: 'Nouveaux ce mois',
                                          value:
                                              (_stats['clients_nouveaux_mois'] ??
                                                      0)
                                                  .toString(),
                                          icon: Icons.person_add,
                                          color: const Color(0xFFfee140),
                                        ),
                                      ],
                                    ),

                                    const SizedBox(height: 32),

                                    // Réparations récentes
                                    Text(
                                      'Réparations récentes',
                                      style: GoogleFonts.poppins(
                                        fontSize: 20,
                                        fontWeight: FontWeight.w600,
                                      ),
                                    ),
                                    const SizedBox(height: 16),
                                    Container(
                                      decoration: BoxDecoration(
                                        color: Colors.white,
                                        borderRadius: BorderRadius.circular(12),
                                        boxShadow: [
                                          BoxShadow(
                                            color: Colors.black
                                                .withValues(alpha: 0.05),
                                            blurRadius: 10,
                                            offset: const Offset(0, 2),
                                          ),
                                        ],
                                      ),
                                      child: _recentReparations.isEmpty
                                          ? const Padding(
                                              padding: EdgeInsets.all(24),
                                              child: Center(
                                                child: Text(
                                                    'Aucune réparation récente'),
                                              ),
                                            )
                                          : ListView.separated(
                                              shrinkWrap: true,
                                              physics:
                                                  const NeverScrollableScrollPhysics(),
                                              itemCount:
                                                  _recentReparations.length,
                                              separatorBuilder: (_, __) =>
                                                  Divider(
                                                      height: 1,
                                                      color: Colors.grey[200]),
                                              itemBuilder: (context, index) {
                                                final rep =
                                                    _recentReparations[index];
                                                return ListTile(
                                                  contentPadding:
                                                      const EdgeInsets
                                                          .symmetric(
                                                          horizontal: 20,
                                                          vertical: 8),
                                                  leading: Container(
                                                    width: 48,
                                                    height: 48,
                                                    decoration: BoxDecoration(
                                                      color: _getStatusColor(
                                                              rep.status)
                                                          .withValues(
                                                              alpha: 0.1),
                                                      borderRadius:
                                                          BorderRadius.circular(
                                                              8),
                                                    ),
                                                    child: Icon(
                                                      Icons.smartphone,
                                                      color: _getStatusColor(
                                                          rep.status),
                                                    ),
                                                  ),
                                                  title: Text(
                                                    '${rep.marque ?? ''} ${rep.appareil}'
                                                        .trim(),
                                                    style: GoogleFonts.poppins(
                                                      fontWeight:
                                                          FontWeight.w500,
                                                    ),
                                                  ),
                                                  subtitle: Text(
                                                    rep.clientFullName.isNotEmpty
                                                        ? rep.clientFullName
                                                        : 'Client inconnu',
                                                    style: TextStyle(
                                                        color: Colors.grey[600]),
                                                  ),
                                                  trailing: Chip(
                                                    label:
                                                        Text(rep.statusLabel),
                                                    backgroundColor:
                                                        _getStatusColor(
                                                                rep.status)
                                                            .withValues(
                                                                alpha: 0.1),
                                                    labelStyle: TextStyle(
                                                      color: _getStatusColor(
                                                          rep.status),
                                                      fontSize: 12,
                                                    ),
                                                  ),
                                                );
                                              },
                                            ),
                                    ),
                                  ],
                                ),
                              ),
                  ),
                ],
              ),
            ),
          ),
        ],
      ),
    );
  }

  Color _getStatusColor(String status) {
    switch (status) {
      case 'en_attente':
        return Colors.orange;
      case 'en_cours':
        return Colors.blue;
      case 'terminee':
        return Colors.green;
      case 'livre':
        return Colors.purple;
      case 'annulee':
        return Colors.red;
      default:
        return Colors.grey;
    }
  }
}
