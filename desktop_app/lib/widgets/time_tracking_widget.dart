import 'package:flutter/material.dart';
import 'dart:async';
import '../../services/auth_service.dart';
import 'package:provider/provider.dart';

/// Widget for employee time tracking (clock in/out)
class TimeTrackingWidget extends StatefulWidget {
  const TimeTrackingWidget({super.key});

  @override
  State<TimeTrackingWidget> createState() => _TimeTrackingWidgetState();
}

class _TimeTrackingWidgetState extends State<TimeTrackingWidget> {
  bool _isClockedIn = false;
  bool _isLoading = true;
  int _elapsedSeconds = 0;
  Timer? _timer;
  String? _clockInTime;

  @override
  void initState() {
    super.initState();
    _fetchStatus();
  }

  @override
  void dispose() {
    _timer?.cancel();
    super.dispose();
  }

  Future<void> _fetchStatus() async {
    setState(() => _isLoading = true);
    try {
      final authService = context.read<AuthService>();
      final api = authService.getApiService();
      final subdomain = authService.getSubdomain();
      final response = await api.getExternal(
        'https://$subdomain.servo.tools/time_tracking_api.php?action=get_status'
      );
      
      if (response['success'] == true && response['data'] != null) {
        final data = response['data'];
        setState(() {
          _isClockedIn = data['is_clocked_in'] == true;
          _elapsedSeconds = data['elapsed_seconds'] ?? 0;
          _clockInTime = data['clock_in'];
        });
        
        if (_isClockedIn) {
          _startTimer();
        }
      }
    } catch (e) {
      debugPrint('TimeTracking Status Error: $e');
    } finally {
      if (mounted) setState(() => _isLoading = false);
    }
  }

  void _startTimer() {
    _timer?.cancel();
    _timer = Timer.periodic(const Duration(seconds: 1), (timer) {
      if (mounted) {
        setState(() => _elapsedSeconds++);
      }
    });
  }

  void _stopTimer() {
    _timer?.cancel();
    _timer = null;
  }

  Future<void> _toggleClock() async {
    setState(() => _isLoading = true);
    try {
      final authService = context.read<AuthService>();
      final api = authService.getApiService();
      final subdomain = authService.getSubdomain();
      final action = _isClockedIn ? 'clock_out' : 'clock_in';
      
      final response = await api.postExternal(
        'https://$subdomain.servo.tools/time_tracking_api.php?action=$action',
        {}
      );
      
      if (response['success'] == true) {
        if (_isClockedIn) {
          _stopTimer();
          setState(() {
            _isClockedIn = false;
            _elapsedSeconds = 0;
            _clockInTime = null;
          });
          if (mounted) {
            ScaffoldMessenger.of(context).showSnackBar(
              const SnackBar(content: Text('Fin de journée enregistrée'), backgroundColor: Colors.orange),
            );
          }
        } else {
          setState(() {
            _isClockedIn = true;
            _elapsedSeconds = 0;
            _clockInTime = DateTime.now().toIso8601String();
          });
          _startTimer();
          if (mounted) {
            ScaffoldMessenger.of(context).showSnackBar(
              const SnackBar(content: Text('Pointage enregistré'), backgroundColor: Colors.green),
            );
          }
        }
      } else {
        throw Exception(response['message'] ?? 'Erreur');
      }
    } catch (e) {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(content: Text('Erreur: $e'), backgroundColor: Colors.red),
        );
      }
    } finally {
      if (mounted) setState(() => _isLoading = false);
    }
  }

  String _formatElapsed(int seconds) {
    final hours = seconds ~/ 3600;
    final minutes = (seconds % 3600) ~/ 60;
    final secs = seconds % 60;
    return '${hours.toString().padLeft(2, '0')}:${minutes.toString().padLeft(2, '0')}:${secs.toString().padLeft(2, '0')}';
  }

  @override
  Widget build(BuildContext context) {
    final isDark = Theme.of(context).brightness == Brightness.dark;
    final bgColor = isDark ? const Color(0xFF1E293B) : Colors.white;
    final textColor = isDark ? Colors.white : Colors.black87;

    return Container(
      padding: const EdgeInsets.all(20),
      decoration: BoxDecoration(
        color: bgColor,
        borderRadius: BorderRadius.circular(16),
        boxShadow: [
          BoxShadow(
            color: Colors.black.withOpacity(0.1),
            blurRadius: 10,
            offset: const Offset(0, 4),
          ),
        ],
      ),
      child: Column(
        mainAxisSize: MainAxisSize.min,
        children: [
          // Header
          Row(
            children: [
              Container(
                padding: const EdgeInsets.all(10),
                decoration: BoxDecoration(
                  color: _isClockedIn ? Colors.green.withOpacity(0.2) : Colors.grey.withOpacity(0.2),
                  borderRadius: BorderRadius.circular(12),
                ),
                child: Icon(
                  _isClockedIn ? Icons.access_time_filled : Icons.access_time,
                  color: _isClockedIn ? Colors.green : Colors.grey,
                ),
              ),
              const SizedBox(width: 12),
              Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(
                    'Pointage',
                    style: TextStyle(fontSize: 16, fontWeight: FontWeight.bold, color: textColor),
                  ),
                  Text(
                    _isClockedIn ? 'En cours' : 'Non pointé',
                    style: TextStyle(fontSize: 12, color: _isClockedIn ? Colors.green : Colors.grey),
                  ),
                ],
              ),
            ],
          ),
          
          const SizedBox(height: 20),

          // Timer Display
          if (_isClockedIn)
            Container(
              padding: const EdgeInsets.symmetric(vertical: 16, horizontal: 24),
              decoration: BoxDecoration(
                color: isDark ? const Color(0xFF0F172A) : Colors.grey[100],
                borderRadius: BorderRadius.circular(12),
              ),
              child: Column(
                children: [
                  Text(
                    _formatElapsed(_elapsedSeconds),
                    style: TextStyle(
                      fontSize: 32,
                      fontWeight: FontWeight.bold,
                      fontFamily: 'monospace',
                      color: Colors.green,
                    ),
                  ),
                  const SizedBox(height: 4),
                  Text(
                    'Temps de travail',
                    style: TextStyle(fontSize: 12, color: textColor.withOpacity(0.6)),
                  ),
                ],
              ),
            ),

          const SizedBox(height: 20),

          // Clock Button
          SizedBox(
            width: double.infinity,
            child: ElevatedButton.icon(
              onPressed: _isLoading ? null : _toggleClock,
              icon: _isLoading 
                ? const SizedBox(width: 20, height: 20, child: CircularProgressIndicator(strokeWidth: 2, color: Colors.white))
                : Icon(_isClockedIn ? Icons.logout : Icons.login),
              label: Text(_isClockedIn ? 'Fin de journée' : 'Pointer'),
              style: ElevatedButton.styleFrom(
                backgroundColor: _isClockedIn ? Colors.orange : Colors.green,
                foregroundColor: Colors.white,
                padding: const EdgeInsets.symmetric(vertical: 16),
                shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
              ),
            ),
          ),
        ],
      ),
    );
  }
}
