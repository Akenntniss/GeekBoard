import Cocoa
import FlutterMacOS

class MainFlutterWindow: NSWindow {
  override func awakeFromNib() {
    let flutterViewController = FlutterViewController()
    let windowFrame = self.frame
    self.contentViewController = flutterViewController
    self.setFrame(windowFrame, display: true)
    
    // Set minimum window size (width x height)
    self.minSize = NSSize(width: 1024, height: 700)
    
    // Set default window size if needed
    if windowFrame.width < 1024 || windowFrame.height < 700 {
      self.setFrame(NSRect(x: windowFrame.origin.x, y: windowFrame.origin.y, width: 1200, height: 800), display: true)
    }

    RegisterGeneratedPlugins(registry: flutterViewController)

    super.awakeFromNib()
  }
}
