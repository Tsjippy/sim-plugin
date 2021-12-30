=== Custom Sim Nigeria Plugin===
Tags: CSS, JS, and other tweaks
Contributors: Ewald Harmsen
Requires at least: 4.0.0
Tested up to: 4.9.1
Stable tag: 1.0
License: GPLv3 or later
License URI: http://www.gnu.org/licenses/gpl-3.0.html



== Description ==

== Setup ==
Install VSCode
Install NPM
Install XAMPP
Add this to your php.ini:
zend_extension = xdebug
xdebug.mode = debug
xdebug.start_with_request = yes
extension = php_gd2.dll
xdebug.remote_host = "127.0.0.1"
xdebug.remote_port = 9003
xdebug.remote_autostart = 1
xdebug.profiler_enable_trigger = 0
xdebug.remote_enable = 1

uncomment this in php.ini:
sodium #not sure if we need this

download wp 
download website code into plugins folder
cd D:\Local_websites\htdocs\simgen\wp-content\plugins\custom-simnigeria-plugin\includes\js
npm update
npm install webpack webpack-cli --save-dev

create a plain js, easy to read: webpack
create a minified one: npm run build


== debug iphone ==


Followed the steps described by (https://washamdev.com/debug-a-website-in-ios-safari-on-windows/#:%7E:text=Open%20up%20the%20Chrome%20browser,chrome%3A%2F%2Finspect%2F%23devices.&text=Enable%20web%20inspector%20on%20your,and%20browse%20to%20a%20website). I did tried this yesterday (iPAD Pro with iOS 14 and Windows 10) and I can confirm that it works ;-)

Here to summarize the solution for remote debugging iOS devices > iOS 11:

    Install iTunes on your Windows 10 PC

    Install Node.js

    Download the most recent ZIP release file of the remotedebug-ios-webkit-adapter

    Create a new folder named "ios-webkit-debug-proxy-1.8.8-win64-bin" at the following location (assumes you installed Node.js in the default directory):

    %AppData%\npm\node_modules\remotedebug-ios-webkit-adapter\node_modules\vs-libimobile\

    Extract the files from the ZIP to that folder %AppData%\npm\node_modules\remotedebug-ios-webkit-adapter\node_modules\vs-libimobile\ios-webkit-debug-proxy-1.8.8-win64-bin

--> The folder vs-libimobile was missing in my case thus I simply created it

    Edit the iosAdapter.js file.

    Open the file from the following location: %AppData%\npm\node_modules\remotedebug-ios-webkit-adapter\out\adapters\iosAdapter.js

On line 125ff., change the proxy variable to the following value (path to the ois_webkit_debug_proxy.exe):

const proxy = path.resolve(__dirname, '../../node_modules/vs-libimobile/ios-webkit-debug-proxy-1.8.8-win64-bin/ios_webkit_debug_proxy.exe');

    Go to %AppData%\npm, open PowerShell and tpe in the following command:

.\remotedebug_ios_webkit_adapter --port=9000

    Open up Chrome on your Win PC and browse to chrome://inspect/#devices

    Since we set the adapter to listen on port 9000, we need to add a network target. Click “Configure” next to Discover network targets:

    Enable web inspector on your iOS device. Take your iOS device and go to Settings > Safari > Advanced and enable Web Inspector

    Open Safari on your iOS device and browse to a website. You should almost immediately see the website appear in Chrome under the Remote Target section.



== Changelog ==

= 1.0.0 =
* Initial release.