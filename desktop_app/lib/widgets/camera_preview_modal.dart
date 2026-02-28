
import 'package:camera/camera.dart';
import 'package:flutter/material.dart';

class CameraPreviewModal extends StatefulWidget {
  const CameraPreviewModal({Key? key}) : super(key: key);

  @override
  State<CameraPreviewModal> createState() => _CameraPreviewModalState();
}

class _CameraPreviewModalState extends State<CameraPreviewModal> {
  CameraController? _controller;
  List<CameraDescription>? _cameras;
  bool _isInitialized = false;
  String? _errorMessage;

  @override
  void initState() {
    super.initState();
    _initCamera();
  }

  Future<void> _initCamera() async {
    try {
      _cameras = await availableCameras();
      if (_cameras == null || _cameras!.isEmpty) {
        setState(() => _errorMessage = "Aucune caméra détectée");
        return;
      }

      // Préférence pour la caméra frontale ou la première disponible
      final camera = _cameras!.firstWhere(
        (c) => c.lensDirection == CameraLensDirection.front, 
        orElse: () => _cameras!.first
      );

      _controller = CameraController(
        camera, 
        ResolutionPreset.medium,
        enableAudio: false
      );

      await _controller!.initialize();
      if (mounted) {
        setState(() => _isInitialized = true);
      }
    } catch (e) {
      if (mounted) {
        setState(() => _errorMessage = "Erreur caméra: $e");
      }
    }
  }

  Future<void> _takePicture() async {
    if (_controller == null || !_controller!.value.isInitialized) return;

    try {
      final XFile file = await _controller!.takePicture();
      if (mounted) {
        Navigator.pop(context, file);
      }
    } catch (e) {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text("Erreur capture: $e"))
      );
    }
  }

  @override
  void dispose() {
    _controller?.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    return Dialog(
      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
      child: Container(
        width: 600,
        height: 500,
        padding: const EdgeInsets.all(16),
        child: Column(
          children: [
            Row(
              mainAxisAlignment: MainAxisAlignment.spaceBetween,
              children: [
                const Text("Prendre une photo", style: TextStyle(fontSize: 18, fontWeight: FontWeight.bold)),
                IconButton(icon: const Icon(Icons.close), onPressed: () => Navigator.pop(context)),
              ],
            ),
            const SizedBox(height: 16),
            Expanded(
              child: _errorMessage != null
                  ? Center(child: Text(_errorMessage!, style: const TextStyle(color: Colors.red)))
                  : _isInitialized
                      ? ClipRRect(
                          borderRadius: BorderRadius.circular(12),
                          child: CameraPreview(_controller!),
                        )
                      : const Center(child: CircularProgressIndicator()),
            ),
            const SizedBox(height: 16),
            ElevatedButton.icon(
              onPressed: _isInitialized ? _takePicture : null,
              icon: const Icon(Icons.camera_alt),
              label: const Text("Capturer"),
              style: ElevatedButton.styleFrom(
                padding: const EdgeInsets.symmetric(horizontal: 32, vertical: 16),
              ),
            )
          ],
        ),
      ),
    );
  }
}
