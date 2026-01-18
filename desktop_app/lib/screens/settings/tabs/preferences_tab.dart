import 'package:flutter/material.dart';

class PreferencesTab extends StatefulWidget {
  final Map<String, dynamic>? preferences;
  const PreferencesTab({super.key, this.preferences});

  @override
  State<PreferencesTab> createState() => _PreferencesTabState();
}

class _PreferencesTabState extends State<PreferencesTab> {
  // Mock implementations for now, will connect to API later
  String _timezone = 'Europe/Paris';

  @override
  void initState() {
    super.initState();
    if (widget.preferences != null) {
      // _timezone = widget.preferences!['timezone'] ?? 'Europe/Paris'; // If available in API
    }
  }

  @override
  Widget build(BuildContext context) {
    return SingleChildScrollView(
      padding: const EdgeInsets.only(bottom: 24),
      child: Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        _buildSectionTitle("Région"),
        _buildDropdown("Fuseau horaire", _timezone, ['Europe/Paris', 'Europe/London', 'UTC'], (val) {
          if (val != null) setState(() => _timezone = val);
        }),
        
        const SizedBox(height: 32),
        SizedBox(
          width: double.infinity,
          child: ElevatedButton(
            onPressed: () {
              ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text("Préférences sauvegardées (Simulation)")));
            },
            style: ElevatedButton.styleFrom(
              backgroundColor: Colors.blue,
              padding: const EdgeInsets.symmetric(vertical: 16),
            ),
            child: const Text("Enregistrer les préférences"),
          ),
        ),
      ],
      ),
    );
  }

  Widget _buildSectionTitle(String title) {
    return Padding(
      padding: const EdgeInsets.only(bottom: 12.0),
      child: Text(title, style: const TextStyle(color: Colors.blue, fontSize: 16, fontWeight: FontWeight.bold)),
    );
  }

  Widget _buildDropdown(String label, String value, List<String> options, ValueChanged<String?> onChanged) {
    return Padding(
      padding: const EdgeInsets.only(bottom: 16.0),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(label, style: TextStyle(color: Theme.of(context).textTheme.bodyMedium?.color, fontSize: 14)),
          const SizedBox(height: 8),
          Container(
            padding: const EdgeInsets.symmetric(horizontal: 16),
            decoration: BoxDecoration(
              color: Theme.of(context).cardColor,
              borderRadius: BorderRadius.circular(8),
            ),
            child: DropdownButtonHideUnderline(
              child: DropdownButton<String>(
                value: value,
                isExpanded: true,
                dropdownColor: Theme.of(context).cardColor,
                style: TextStyle(color: Theme.of(context).textTheme.bodyLarge?.color),
                items: options.map((opt) => DropdownMenuItem(value: opt, child: Text(opt))).toList(),
                onChanged: onChanged,
              ),
            ),
          ),
        ],
      ),
    );
  }
}
