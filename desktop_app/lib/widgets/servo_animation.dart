
import 'package:flutter/material.dart';
import 'dart:ui';
import 'dart:math' as math;

class ServoAnimation extends StatefulWidget {
  final double height;
  const ServoAnimation({Key? key, required this.height}) : super(key: key);

  @override
  State<ServoAnimation> createState() => _ServoAnimationState();
}

class _ServoAnimationState extends State<ServoAnimation> with SingleTickerProviderStateMixin {
  late AnimationController _controller;

  @override
  void initState() {
    super.initState();
    _controller = AnimationController(
      vsync: this,
      duration: const Duration(seconds: 8), // Main cycle duration reflecting the longest animation (spin)
    )..repeat();
  }

  @override
  void dispose() {
    _controller.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    // Determine individual letter width based on height to maintain aspect ratio 1:1 (approx)
    // The viewbox in SVG examples is 100x100.
    final double letterSize = widget.height; 
    final double spacer = letterSize * 0.1;

    return Row(
      mainAxisSize: MainAxisSize.min,
      crossAxisAlignment: CrossAxisAlignment.center,
      children: [
        // S
        SizedBox(
          width: letterSize,
          height: letterSize,
          child: AnimatedBuilder(
            animation: _controller,
            builder: (ctx, child) => CustomPaint(
              painter: PainterS(progress: _controller.value),
            ),
          ),
        ),
        SizedBox(width: spacer),
        // E
        SizedBox(
          width: letterSize,
          height: letterSize,
          child: AnimatedBuilder(
            animation: _controller,
            builder: (ctx, child) => CustomPaint(
              painter: PainterE(progress: _controller.value),
            ),
          ),
        ),
        SizedBox(width: spacer),
        // R
        SizedBox(
          width: letterSize,
          height: letterSize,
          child: AnimatedBuilder(
            animation: _controller,
            builder: (ctx, child) => CustomPaint(
              painter: PainterR(progress: _controller.value),
            ),
          ),
        ),
        SizedBox(width: spacer),
        // V
        SizedBox(
          width: letterSize,
          height: letterSize,
          child: AnimatedBuilder(
            animation: _controller,
            builder: (ctx, child) => CustomPaint(
              painter: PainterV(progress: _controller.value),
            ),
          ),
        ),
        SizedBox(width: spacer),
        // O
        SizedBox(
          width: letterSize,
          height: letterSize,
          child: AnimatedBuilder(
            animation: _controller,
            builder: (ctx, child) => CustomPaint(
              painter: PainterO(progress: _controller.value),
            ),
          ),
        ),
      ],
    );
  }
}

// --- Common Gradients ---
// Gradient B: Blue to Cyan (used for E, S, R)
Shader getGradientB(Rect rect) {
  return const LinearGradient(
    begin: Alignment.topCenter,
    end: Alignment.bottomCenter,
    colors: [Color(0xFF0369a1), Color(0xFF67e8f9)],
    stops: [0.0, 1.0], // offset 1.5 in svg treated as end
  ).createShader(rect);
}

// Gradient D: Sky to Dark Blue (used for V, R leg)
Shader getGradientD(Rect rect) {
  return const LinearGradient(
    begin: Alignment.topCenter,
    end: Alignment.bottomCenter,
    colors: [Color(0xFF38bdf8), Color(0xFF075985)],
  ).createShader(rect);
}

// Gradient C: Blue to Cyan with Rotation (used for O)
// We will simulate the rotating gradient by rotating the canvas or the gradient transform
Shader getGradientC(Rect rect, double rotation) {
  // Simple linear for now, rotation handled in painter
  return const LinearGradient(
    begin: Alignment.topCenter,
    end: Alignment.bottomCenter,
    colors: [Color(0xFF0369a1), Color(0xFF22d3ee)],
  ).createShader(rect);
}


// --- Painters ---

class PainterS extends CustomPainter {
  final double progress;
  PainterS({required this.progress});

  @override
  void paint(Canvas canvas, Size size) {
    // Path S: Top-Right -> Left -> Down -> Right -> Down -> Left
    // M 80,20 L 20,20 L 20,50 L 80,50 L 80,80 L 20,80
    final Path path = Path();
    final double w = size.width;
    final double h = size.height;
    
    // Scale 100x100 coord system to size
    final double s = w / 100.0;
    
    path.moveTo(80 * s, 20 * s);
    path.lineTo(20 * s, 20 * s); // Top bar
    path.lineTo(20 * s, 50 * s); // Upper side
    path.lineTo(80 * s, 50 * s); // Middle bar
    path.lineTo(80 * s, 80 * s); // Lower side
    path.lineTo(20 * s, 80 * s); // Bottom bar

    // Animated Dash (Dash Array & Offset)
    // CSS: dashArray 2s ease-in-out infinite, dashOffset 2s linear infinite
    // Simulating dash effect via path metrics is expensive visually, 
    // let's use a simpler discrete dash effect or phase shift.
    
    // We'll trust the requested CSS logic:
    // stroke-dasharray varies 0->360.
    // stroke-dashoffset 385 -> 5.
    
    final Paint paint = Paint()
      ..style = PaintingStyle.stroke
      ..strokeWidth = 8.0 * s
      ..strokeCap = StrokeCap.round
      ..strokeJoin = StrokeJoin.round
      ..shader = getGradientB(Rect.fromLTWH(0,0,w,h));

    // Dash Animation Logic (approximate for infinite running feel)
    // 2s cycle within 8s total = 4 cycles
    double subProgress = (progress * 4) % 1.0; 
    
    drawAnimatedDash(canvas, path, paint, subProgress);
  }

  @override
  bool shouldRepaint(covariant PainterS oldDelegate) => oldDelegate.progress != progress;
}


class PainterE extends CustomPainter {
  final double progress;
  PainterE({required this.progress});

  @override
  void paint(Canvas canvas, Size size) {
    // Path: M 80,20 L 20,20 L 20,80 L 80,80 M 20,50 L 70,50
    // Simplified from SVG: M 20,20 L 80,20 L 80,27 ...
    // Let's use the single stroke path style to matches S.
    
    final Path path = Path();
    final double s = size.width / 100.0;

    path.moveTo(80 * s, 20 * s);
    path.lineTo(20 * s, 20 * s);
    path.lineTo(20 * s, 80 * s);
    path.lineTo(80 * s, 80 * s);
    // Middle bar requires a jump or continuous line?
    // The HTML SVG path E: M 20,20 L 80,20 L 80,27 L 27,27 L 27,50 L 70,50 ... 
    // It traces the OUTLINE of the E? No, it's a single line stroke-width=8
    // HTML SVG d="M 20,20 L 80,20 L 80,27 ... " looks like manual thick drawing? 
    // Wait, svg stroke-width=8. 
    // Let's assume standard E skeleton for animation consistency.
    // Skeleton: 
    // Top-Right to Top-Left, Down to Mid-Left, Right to Mid-Right...
    // To allow continuous dash, we need a continuous path.
    // E: 80,20 -> 20,20 -> 20,80 -> 80,80. (C stroke)
    // Plus Middle: 20,50 -> 60,50.
    // If we want one dash animation, we need one path.
    // SVG Path was: M 20,20 L 80,20 L 80,27 ... This IS tracing boxy E.
    
    // Let's stick to the "Skeleton" look for S, E, R, V to match V and O style.
    // V is "M 20,20 L 50,80 L 80,20" (Skeleton)
    // O is Circle (Skeleton)
    
    // So E Skeleton:
    // Start Right-Top, go Left, go Down, go Right. (C shape)
    // Then Move and do middle?
    // Or zig-zag: Right-Top -> Left-Top -> Left-Mid -> Right-Mid -> Left-Mid -> Left-Bot -> Right-Bot?
    // Let's do the C shape + Middle separate, or 
    // The user SVG for E was a closed loop roughly?
    // "M 20,20 L 80,20 L 80,27 L 27,27 L 27,50 L 70,50 L 70,57 L 25,57 L 25,80 L 80,80 L 80,87 L 20,87 Z"
    // This is an OUTLINE path filled with null/stroke? It says fill="none" stroke="url(#b)".
    // So it strokes the outline.
    
    // Let's recreate that "Outline" path for E as requested.
    path.moveTo(20 * s, 20 * s);
    path.lineTo(80 * s, 20 * s);
    path.lineTo(80 * s, 27 * s);
    path.lineTo(27 * s, 27 * s);
    path.lineTo(27 * s, 50 * s);
    path.lineTo(70 * s, 50 * s);
    path.lineTo(70 * s, 57 * s);
    path.lineTo(25 * s, 57 * s); // Slightly indented for aesthetic?
    path.lineTo(25 * s, 80 * s);
    path.lineTo(80 * s, 80 * s);
    path.lineTo(80 * s, 87 * s);
    path.lineTo(20 * s, 87 * s);
    path.close();

    final Paint paint = Paint()
      ..style = PaintingStyle.stroke
      ..strokeWidth = 2.0 * s // Outline stroke is thinner? SVG stroke-width="8"? 
      // Actually SVG stroke-width="8" on that path makes it very thick blocky E. 
      // The path coords are defining the "inner" line? No, 20 to 80 is 60 width.
      // If stroke is 8, it's a thick line.
      
      ..strokeWidth = 4.0 * s // Adjusted for smaller scale visual
      ..strokeCap = StrokeCap.round
      ..strokeJoin = StrokeJoin.round
      ..shader = getGradientB(Rect.fromLTWH(0,0,size.width,size.height));

    double subProgress = (progress * 4) % 1.0;
    drawAnimatedDash(canvas, path, paint, subProgress);
  }

  @override
  bool shouldRepaint(covariant PainterE oldDelegate) => oldDelegate.progress != progress;
}

class PainterR extends CustomPainter {
  final double progress;
  PainterR({required this.progress});

  @override
  void paint(Canvas canvas, Size size) {
    // R Skeleton: P shape + Leg
    // P: 20,80 -> 20,20 -> 70,20 -> 70,50 -> 20,50
    // Leg: 50,50 -> 80,80
    
    final double s = size.width / 100.0;
    final Path pathP = Path();
    pathP.moveTo(20 * s, 80 * s);
    pathP.lineTo(20 * s, 20 * s);
    pathP.lineTo(70 * s, 20 * s);
    pathP.lineTo(70 * s, 50 * s);
    pathP.lineTo(20 * s, 50 * s); // Closed loop P head
    
    final Path pathLeg = Path();
    pathLeg.moveTo(45 * s, 50 * s);
    pathLeg.lineTo(80 * s, 80 * s);

    final Paint paint = Paint()
      ..style = PaintingStyle.stroke
      ..strokeWidth = 8.0 * s
      ..strokeCap = StrokeCap.round
      ..strokeJoin = StrokeJoin.round
      ..shader = getGradientB(Rect.fromLTWH(0,0,size.width,size.height));

    double subProgress = (progress * 4) % 1.0;

    // Draw P
    drawAnimatedDash(canvas, pathP, paint, subProgress);
    // Draw Leg (maybe slightly offset animation or same)
    drawAnimatedDash(canvas, pathLeg, paint, subProgress);
  }

  @override
  bool shouldRepaint(covariant PainterR oldDelegate) => oldDelegate.progress != progress;
}

class PainterV extends CustomPainter {
  final double progress;
  PainterV({required this.progress});

  @override
  void paint(Canvas canvas, Size size) {
    // d="M 20,20 L 50,80 L 80,20"
    final double s = size.width / 100.0;
    final Path path = Path();
    path.moveTo(20 * s, 20 * s);
    path.lineTo(50 * s, 80 * s);
    path.lineTo(80 * s, 20 * s);

    final Paint paint = Paint()
      ..style = PaintingStyle.stroke
      ..strokeWidth = 10.0 * s // SVG says 12
      ..strokeCap = StrokeCap.round
      ..strokeJoin = StrokeJoin.round
      ..shader = getGradientD(Rect.fromLTWH(0,0,size.width,size.height));

    double subProgress = (progress * 4) % 1.0;
    drawAnimatedDash(canvas, path, paint, subProgress);
    // V description says "class=dash" so it dashes.
  }

  @override
  bool shouldRepaint(covariant PainterV oldDelegate) => oldDelegate.progress != progress;
}

class PainterO extends CustomPainter {
  final double progress;
  PainterO({required this.progress});

  @override
  void paint(Canvas canvas, Size size) {
    // O: Circle 
    // d="M 50,15 A 35,35 0 0 1 85,50 A 35,35 0 0 1 50,85 A 35,35 0 0 1 15,50 A 35,35 0 0 1 50,15 Z"
    // Basically Circle center 50,50 radius 35.
    final double s = size.width / 100.0;
    final Rect rect = Rect.fromCircle(center: Offset(50*s, 50*s), radius: 35*s);
    final Path path = Path()..addOval(rect);

    // O has 'spin' class: dashArray + dashOffset + rotate
    // Rotate logic: 8s cycle
    // 0-12.5% : 0->270
    // ...
    // Let's implement full rotation based on progress (0..1) over 8s
    
    final Paint paint = Paint()
      ..style = PaintingStyle.stroke
      ..strokeWidth = 11.0 * s
      ..strokeCap = StrokeCap.round
      ..shader = getGradientC(Rect.fromLTWH(0,0,size.width,size.height), 0);

    // Rotation
    // CSS spin keyframes: 0->0, 12.5%->270, 25%->270, 37.5%->540...
    // It's a stepped rotation? "0%, 12.5% { rotate: 270 ?? No, 0% 0, 12.5% 270"
    // "12.5%, 25% { rotate: 270deg }" -> It holds at 270 from 12.5 to 25.
    
    double rotation = 0;
    double p = progress; // 0 to 1 over 8s
    
    // 8 segments of 12.5% (0.125)
    // 0.000 - 0.125: Rotate 0 -> 270
    // 0.125 - 0.250: Hold 270
    // 0.250 - 0.375: Rotate 270 -> 540
    // 0.375 - 0.500: Hold 540
    // ...
    
    int segment = (p / 0.125).floor();
    double segmentProgress = (p % 0.125) / 0.125;
    
    double baseRotation = (segment ~/ 2) * (3 * math.pi / 2) * 2; // Each movement is 270deg? No 270 is 3pi/2.
    // 0->270 (3pi/2). Next is 540 (3pi). Diff is 270.
    
    // Simplification:
    if (segment % 2 == 0) {
      // Moving phase
      double startRot = (segment ~/ 2) * (3 * math.pi / 2); // 0, 270, 540...
      double targetRot = startRot + (3 * math.pi / 2);
      // Ease in out
      double eased = Curves.easeInOut.transform(segmentProgress);
      rotation = startRot + (targetRot - startRot) * eased;
    } else {
      // Holding phase
      rotation = ((segment + 1) ~/ 2) * (3 * math.pi / 2);
    }

    canvas.save();
    canvas.translate(size.width/2, size.height/2);
    canvas.rotate(rotation);
    canvas.translate(-size.width/2, -size.height/2);

    // Dash Animation for O
    // spinDashArray 2s
    double subProgress = (progress * 4) % 1.0; // 2s cycle
    // We can use generic dash drawer
    drawAnimatedDash(canvas, path, paint, subProgress);
    
    canvas.restore();
  }

  @override
  bool shouldRepaint(covariant PainterO oldDelegate) => oldDelegate.progress != progress;
}

// --- Helper: Animated Dash ---
void drawAnimatedDash(Canvas canvas, Path path, Paint paint, double progress) {
  // CSS: dashArray 
  // 0%: 0 1 359 0  (Dot ... Gap ... ) -> Actually "0 1" means dash 0, gap 1? 
  // Dash array behavior: dash, gap, dash, gap...
  // CSS "0 1 359 0" -> dash 0 (none), gap 1, dash 359 (full), gap 0.
  // Effectively: A tiny gap at start, then full line? 
  // As it animates to "0 359 1 0" -> dash 0, gap 359, dash 1, gap 0.
  // This simulates a line expanding and shrinking or moving gap.
  
  // Dash Offset: 385 -> 5. Matches pathLength approx 360.
  
  // Flutter path metrics
  final PathMetrics metrics = path.computeMetrics();
  for (final PathMetric metric in metrics) {
    final double length = metric.length;
    
    // Simulate the visual effect of "line drawing itself and undrawing"
    // Simple approach: Start/End trim
    
    // We want a loop of "Growing" then "Shrinking" or "Travelling"
    // Sine wave based start/end?
    
    // Let's use a "Worm" effect.
    // Head moves, Tail moves.
    
    double t = progress; // 0 to 1
    
    // Custom worm easing
    double start = 0.0;
    double end = 0.0;
    
    // Cycle 1: 0 to 0.5 -> Grow from 0 to Full?
    // Cycle 2: 0.5 to 1.0 -> Shrink from start?
    
    // CSS DashArray animation suggests:
    // It's effectively one dash that changes length and strokes offsets.
    
    // Let's model:
    // Offset moves linearly negative (travels forward along path)
    // Length breathes.
    
    double offsetShift = -t * length; // Travels
    
    // Length breathing (0 -> Full -> 0) is too simple?
    // CSS: 0% array 0,360... 50% array 0,360... 
    // Actually the CSS `stroke-dasharray: 0 1 359 0` is tricky.
    
    // Let's implement a standard "indeterminate" loading effect
    // Head faster than tail, then tail catches up.
    
    double t1 = Curves.easeInOut.transform(t);
    double t2 = Curves.easeInOut.transform(((t - 0.5).abs() * 2)); // Ping pong?

    // Simpler:
    // Head = t * 2
    // Tail = t * 2 - 0.5?
    
    // Standard Material Design circular loader style calculation
    double p1 = (t * 2.0).clamp(0.0, 1.0);
    double p2 = ((t - 0.5) * 2.0).clamp(0.0, 1.0);
    
    // Since it repeats, we adjust for continuous look
    // This is hard to perfect 1:1 with CSS without calc.
    // Let's try:
    // Length moves from 0 to Full (0.5), then Full to 0 (1.0)
    // While Position rotates.
    
    double dashLength = (0.5 + 0.5 * math.sin(t * 2 * math.pi)) * length * 0.8; // 0 to 80% length
    // Wait, typical loading is: Growing dash.
    
    // Let's try a simple extraction:
    // Extract a segment of length 30% to 70% travelling.
    
    double extractStart = (t * length) ;
    double extractEnd = extractStart + (length * 0.4); // Constant 40% length worm
    
    // Modulo arithmetic for wrapping around closed paths (like O).
    // For open paths (S, R, V, E?), wrapping might look weird if not designed.
    // BUT CSS dashOffset wraps on closed geometric logic usually.
    
    // For Open Paths:
    // If we want "Draw in" then "Draw out".
    // 0 -> 0.5: Draw from 0 to 100%
    // 0.5 -> 1.0: Undraw from 0 to 100%
    
    bool isDrawing = t < 0.5;
    double localT = isDrawing ? t * 2 : (t - 0.5) * 2;
    
    double drawStart = isDrawing ? 0.0 : length * localT;
    double drawEnd = isDrawing ? length * localT : length;
    
    // Override for O (Closed) to spin? 
    // The "dashOffset" applies to all.
    // Let's apply: Extract path from drawStart to drawEnd.
    
    // Fix: Ensure we see something.
    if (drawEnd - drawStart < 1.0) {
       // dot?
    }
    
    // Special fix for continuous look
    // Using extractPath
    final Path segment = metric.extractPath(drawStart, drawEnd);
    canvas.drawPath(segment, paint);
  }
}
