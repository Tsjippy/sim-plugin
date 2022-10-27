from pydbus import SystemBus
from gi.repository import GLib
import subprocess
import pathlib
folder  = pathlib.Path(__file__).parent.resolve()

def msg_rcv (timestamp, source, group_id, message, attachments):
    subprocess.call(["php", folder+"/daemon2.php", timestamp, source, group_id, message, attachments])

bus                         = SystemBus()
signal                      = bus.get('org.asamk.Signal')
signal.onMessageReceivedV2  = msg_rcv