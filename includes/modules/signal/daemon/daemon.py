from pydbus import SessionBus
from gi.repository import GLib
import subprocess
import pathlib
folder  = str(pathlib.Path(__file__).parent.resolve())

print(folder)

def msg_rcv (timestamp, source, group_id, message, attachments):
    subprocess.call(["php", folder+"/daemon.php", str(timestamp), str(source), str(group_id), str(message), str(attachments)])

bus                         = SessionBus()
loop                        = GLib.MainLoop()

signal                      = bus.get('org.asamk.Signal')
signal.onMessageReceivedV2  = msg_rcv

loop.run()