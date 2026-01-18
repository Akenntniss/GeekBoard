import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import '../../../../services/auth_service.dart';
import '../../../../config/api_config.dart';
import '../../../../services/api_service.dart';

class QuoteRelanceTab extends StatefulWidget {
  const QuoteRelanceTab({super.key});

  @override
  State<QuoteRelanceTab> createState() => _QuoteRelanceTabState();
}

class _QuoteRelanceTabState extends State<QuoteRelanceTab> {
  late final ApiService _apiService;
  bool _isLoading = true;
  bool _isSaving = false;
  
  bool _isActive = false;
  List<TimeOfDay> _times = [];

  @override
  void initState() {
    super.initState();
    _apiService = context.read<AuthService>().getApiService();
    _loadData();
  }

  Future<void> _loadData() async {
    try {
      final response = await _apiService.get(ApiConfig.settingsRelanceEndpoint);
      if (response != null && response['success'] == true) {
        final data = response['data'];
        final timesList = List<String>.from(data['relances_horaires'] ?? []);
        
        setState(() {
          _isActive = data['est_active'] == true;
          _times = timesList.map((t) {
            final parts = t.split(':');
            return TimeOfDay(hour: int.parse(parts[0]), minute: int.parse(parts[1]));
          }).toList();
          
          if (_times.isEmpty) {
            _times = [const TimeOfDay(hour: 9, minute: 0)]; // Default
          }
          
          _isLoading = false;
        });
      } else {
         // Handle error or use defaults
         setState(() {
           _isLoading = false;
           _times = [const TimeOfDay(hour: 9, minute: 0)];
         });
      }
    } catch (e) {
      if (mounted) setState(() => _isLoading = false);
      print("Error loading relance config: $e");
    }
  }

  Future<void> _saveSettings() async {
    setState(() => _isSaving = true);
    try {
      final timesStrings = _times.map((t) {
        return '${t.hour.toString().padLeft(2, '0')}:${t.minute.toString().padLeft(2, '0')}';
      }).toList();

      final payload = {
        'est_active': _isActive ? 1 : 0,
        'relances_horaires': timesStrings,
      };

      final response = await _apiService.post(ApiConfig.settingsRelanceEndpoint, payload);
      
      if (response != null && response['success'] == true) {
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(content: Text('Configuration enregistrée'), backgroundColor: Colors.green),
        );
      } else {
        throw Exception(response?['message'] ?? 'Erreur inconnue');
      }
    } catch (e) {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text('Erreur: $e'), backgroundColor: Colors.red),
      );
    } finally {
      if (mounted) setState(() => _isSaving = false);
    }
  }

  Future<void> _addTime() async {
    final now = TimeOfDay.now();
    final newTime = await showTimePicker(context: context, initialTime: now);
    if (newTime != null) {
      setState(() {
        _times.add(newTime);
      });
    }
  }

  void _removeTime(int index) {
    setState(() {
      _times.removeAt(index);
    });
  }

  @override
  Widget build(BuildContext context) {
    if (_isLoading) return const Center(child: CircularProgressIndicator());

    return SingleChildScrollView(
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          _buildInfoCard(),
          const SizedBox(height: 24),
          _buildConfigCard(),
        ],
      ),
    );
  }

  Widget _buildInfoCard() {
    return Container(
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        color: Colors.blue.withOpacity(0.1),
        border: Border.all(color: Colors.blue.withOpacity(0.3)),
        borderRadius: BorderRadius.circular(12),
      ),
      child: const Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Icon(Icons.info, color: Colors.blue),
          SizedBox(width: 16),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text("Fonctionnement RELANCE", style: TextStyle(fontWeight: FontWeight.bold, color: Colors.blue)),
                SizedBox(height: 4),
                Text(
                  "Les relances automatiques envoient des SMS aux clients pour les devis en attente et les devis expirés depuis moins de 15 jours aux heures que vous définissez. Maximum 10 relances par jour.",
                  style: TextStyle(fontSize: 13),
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildConfigCard() {
    return Card(
      elevation: 0,
      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12), side: BorderSide(color: Theme.of(context).dividerColor)),
      child: Padding(
        padding: const EdgeInsets.all(24.0),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            const Text("Configuration des relances", style: TextStyle(fontSize: 18, fontWeight: FontWeight.bold)),
            const SizedBox(height: 24),
            
            SwitchListTile(
              title: const Text("Activer les relances automatiques", style: TextStyle(fontWeight: FontWeight.bold)),
              subtitle: const Text("Activer ou désactiver l'envoi automatique"),
              value: _isActive,
              onChanged: (val) => setState(() => _isActive = val),
              contentPadding: EdgeInsets.zero,
            ),
            
            if (_isActive || true) ...[ // Always show for editing if desired, or adhere to web which hides it. Web hides it.
              // Logic check: web uses style="display: none" if not active.
              if (_isActive) ... [
                const SizedBox(height: 24),
                const Text("Horaires des relances", style: TextStyle(fontWeight: FontWeight.bold)),
                const SizedBox(height: 12),
                
                ..._times.asMap().entries.map((entry) {
                  final index = entry.key;
                  final time = entry.value;
                  return Padding(
                    padding: const EdgeInsets.only(bottom: 8.0),
                    child: Row(
                      children: [
                        Container(
                          padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 8),
                          decoration: BoxDecoration(
                            border: Border.all(color: Colors.grey[300]!),
                            borderRadius: BorderRadius.circular(8),
                          ),
                          child: Text("Relance ${index + 1}:  ${time.format(context)}"),
                        ),
                        if (_times.length > 1)
                          IconButton(
                            icon: const Icon(Icons.delete, color: Colors.red),
                            onPressed: () => _removeTime(index),
                          )
                      ],
                    ),
                  );
                }),
                
                const SizedBox(height: 12),
                OutlinedButton.icon(
                  onPressed: _addTime,
                  icon: const Icon(Icons.add),
                  label: const Text("Ajouter une relance"),
                ),
              ]
            ],
            
            const SizedBox(height: 32),
             SizedBox(
               width: double.infinity,
               child: ElevatedButton.icon(
                 onPressed: _isSaving ? null : _saveSettings,
                 icon: _isSaving ? const SizedBox(width: 16, height: 16, child: CircularProgressIndicator(strokeWidth: 2, color: Colors.white)) : const Icon(Icons.save),
                 label: const Text("Enregistrer les paramètres"),
                 style: ElevatedButton.styleFrom(
                   backgroundColor: Theme.of(context).primaryColor,
                   foregroundColor: Colors.white,
                   padding: const EdgeInsets.symmetric(vertical: 16),
                 ),
               ),
             ),
          ],
        ),
      ),
    );
  }
}
