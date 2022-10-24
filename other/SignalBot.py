# https://github.com/AsamK/signal-cli/wiki/DBus-service
# pip3 install pydbus
# pip3 install timeloop
# sudo cp signal-cli/data/org.asamk.Signal.conf /etc/dbus-1/system.d/
# sudo cp signal-cli/data/org.asamk.Signal.service /usr/share/dbus-1/system-services/
# sudo cp signal-cli/data/signal-cli.service /etc/systemd/system/
# sudo cp signal-cli-bot.service /etc/systemd/system/

# sudo systemctl daemon-reload
# sudo systemctl enable signal-cli.service
# sudo systemctl enable signal-cli-bot.service
# sudo systemctl reload dbus.service
# /usr/local/bin/signal-cli --dbus-system send -m "Message" +COUNTRYCODE NUMBER
# connected to +COUNTRYCODE NUMBER

#copy files from windows: pscp -P 22 "C:\Users\ewald\Downloads\signalbot.db" signal-cli@IPADDRESS:/home/signal-cli/
#copy files from linux: pscp -P 22 signal-cli@IPADDRESS:/home/signal-cli/SignalBot.py D:\


from pydbus import SystemBus
from gi.repository import GLib
import base64
import datetime
import requests
from requests.exceptions import Timeout
from lxml import html
import json
import sqlite3 as sl
import re
import urllib.parse
import holidays
import os


########################################
# VARIABLES
#############################
con = sl.connect('/home/signal-cli/signalbot.db')

#Group ID
groupid = ""

#test group id
testgroupid = ""

groupid_array = []
for i in base64.b64decode(groupid.encode()):
    groupid_array.append(i)

###################################################
# FUNCTIONS
##################################################

#Create db table
with con:
    # recipients table
    con.execute("""
        CREATE TABLE IF NOT EXISTS RECIPIENT (
            id INTEGER NOT NULL PRIMARY KEY AUTOINCREMENT,
            type TEXT,
            name TEXT,
            number TEXT,
            hour INTEGER
        );
    """)
    
    # group members table
    con.execute("""
        CREATE TABLE IF NOT EXISTS GROUPMEMBERS (
            id INTEGER NOT NULL PRIMARY KEY AUTOINCREMENT,
            groupname TEXT,
            groupid TEXT,
            members TEXT
        );
    """)
    
def schedule_prayer_request(type, name, number, hour):
    sql = 'INSERT INTO RECIPIENT (type, name, number, hour) values(?, ?, ?, ?)'
    data = [
        (type, name, str(number), hour)
    ]
    
    with con:
        con.executemany(sql, data)

    return

#Store contact name
def store_contact(number,name):
    if name == '':
        return "you did not tell me your name?"
    else:
        signal.setContactName(number,name)
        return "Nice to meet you "+name+'!'
    
def age():
    birthday = datetime.date(2021, 2, 3)
    delta = datetime.datetime.now().date() - birthday
    
    if delta.days < 365:
        return "I am "+str(delta.days)+" days old and you?"
    else:
        return "I am "+str(delta.days/(365.25))+" years old and you?"

def remove_html_tags(text):
    """Remove html tags from a string"""
    import re
    clean = re.compile('<.*?>')
    return re.sub(clean, '', text)
    
#get website info
def get_websiteinfo(command):
    try:
        print_to_log("Trying to get a result for command "+command)

        url = "https://YOURURL/wp-json/sim/v2/"+command
        verify = True
        # verify = False
        USERNAME = USERNAME
        PASSWORD = APPLICATION PASSWORD
        credentials = USERNAME + ':' + PASSWORD
        token = base64.b64encode(credentials.encode())
        header = {
        'Authorization': 'Basic ' + token.decode('utf-8'),
        'User-Agent': 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10.12; rv:55.0) Gecko/20100101 Firefox/55.0',
        }
        
        print_to_log("Requesting "+command)
        response = requests.get(url, headers=header, verify=verify, timeout=(2,120)).json()
        #json_response = json.loads(response.content)
        print_to_log("Got this from the website:\n "+str(response))
        
        if command == 'prayermessage' and response == False:
            print_to_log("Trying again to get a result")
            response = get_websiteinfo(command)
            
        return response
    except Timeout:
        print_to_log("Requesting "+command+" timedout")
        return ''
    else:
        return ''
    

#determine the part of the day
def get_part_of_day():
    hour = datetime.datetime.now().hour
    return (
        "morning" if 5 <= hour <= 11
        else
        "afternoon" if 12 <= hour <= 17
        else
        "evening" if 18 <= hour <= 22
        else
        "night"
    )

#determine the answer
def bot_answer(message,type,source,name):
    msg = message.lower()
    
    print_to_log("Getting answer for: "+msg)
    
    if "how old" in msg or "what is your age" in msg:
        return age()
    elif msg == "hi" or msg == "hello" or msg == "hi there":
        return "hi " + name
    elif msg == "good morning" or msg == "good afternoon" or msg == "good evening" or msg == "good night":
        return "A very good "+get_part_of_day()+" to you as well!"
    elif "how are you" in msg:
        return "I am good, how are you?"
    elif 'my name is' in msg:
        redata = re.compile(re.escape('my name is'), re.IGNORECASE)
        name = redata.sub('', message).strip()
        return store_contact(source,name)
    elif 'schedule' in msg and 'prayer' in msg:
        if 'PM' in message or 'AM' in message:
            time        = re.search(r'(\d{1,2}) ?([AP]M)',message)
            hour        = int(time.group(1))
            fraction    = time.group(2)
            if fraction == 'PM':
                hour += 12
        elif(':' in msg):
            time        = re.search(r'(\d{1,2}):(\d{1,2})',message)
            hour        = int(time.group(1))
        else:
            return "I don't know when to schedule the prayer request for you\nInclude a time in the form of '9AM' or '15:00' in your request"
            
        with con:
            if type == 'group':
                text = 'this group'
                try:
                    name = signal.getGroupName(source)
                except:
                    name = ''
                source = json.dumps(source)
                print(source)
                #source = str(source).replace('[','').replace(']','').replace(',','')
                
            else:
                text = 'you'
                try:
                    name = signal.getContactName(source)
                except:
                    name = ''
            data = con.execute("SELECT * FROM RECIPIENT WHERE number = '"+str(source)+"'")
            row = data.fetchone()
            #check if there is already an subscription for this source
            if row==None:                    
                schedule_prayer_request(type, name, source, hour)
                    
                return "From now on I will send daily prayer requests to "+text+" around "+str(hour)+" o'clock\n\nHere is the first one:\n"+get_websiteinfo('prayermessage')
            else:
                return "You already have a subscription at "+str(row[4])+" o'clock"
    elif 'remove' in msg and 'prayer' in msg:
        with con:
            if type == 'group':
                source = json.dumps(source)
            data = con.execute("SELECT * FROM RECIPIENT WHERE number = '"+str(source)+"'")
            
            if data.fetchone()!=None:
                con.execute("DELETE FROM RECIPIENT WHERE number = '"+str(source)+"'")
                return "Succesfully deleted all your prayer request schedules"
            else:
                return "There was nothing to delete"

    elif "prayer" in msg:
        print_to_log("Getting Prayerrequest")
        return get_websiteinfo('prayermessage')
    elif "help" in msg or "which answers" in msg or "which questions" in msg:
        return "These are the keywords I listen to:\n\
'prayer': sends today prayer request\n\n\
'schedule prayerrequest for HOUR NUMBER AM/PM': \nexample1: schedule prayerrequest for 9PM\nexample2: schedule prayerrequest for 13:00 \n\n\
'remove prayer': removes any prayer request schedules\n\n\
;'how old' or 'what is your age':  responds with the bot age\n\n\
'good morning'\n\n\
'my name is': Tells me your name\n\n\
'hi' or 'hello'\n\n\
'help': shows this message"
    else:
        return "I have no answer to "+message

def findName(number):
    name = signal.getContactName(number)

    if name == '':
        #find name from website
        name = get_websiteinfo("firstname/?phone="+urllib.parse.quote_plus(number))
        #Website does not know this number
        if name == "not found" or name == '':
            name = ""
        else:
            #store the name in contacts
            store_contact(number,name)
            
    return name
    
def print_to_log(msg):
    print(msg)
    
    now = datetime.datetime.now()
    dt_string = now.strftime("%d-%m-%Y %H:%M:%S")
    f = open("/home/signal-cli/signal_bot_log.txt", "a")
    f.write(dt_string+": "+msg+"\n")
    f.close()
    return
    
#run when a signal message is received
def msgRcv (timestamp, source, groupID, message, attachments):
    print_to_log("Message received: "+message)
    print(timestamp,message,source,groupID,attachments)
    #f = open("/home/signal-cli/signal_bot_log.txt", "a")
    #f.write(str(timestamp)+"\n")
    #f.write(message+"\n")
    #f.write(str(source)+"\n")
    #f.write(str(groupID)+"\n")
    #f.close()
    
    #message should not be empty
    if message != '':
        #are we receiving from a group or an person?
        if len(groupID) == 0:
            #Person
            try:
                name = findName(source)
                print_to_log("name is: "+name)
                
                answer = bot_answer(message,'person',source,name)
                print_to_log("Answer is: "+answer)
                
                signal.sendMessage(answer, [],source)
                
                print_to_log("Bot answer send")
                
                if 'my name is' not in message.lower() and name == "":
                    signal.sendMessage("I do not know you yet, please tell me your name by sending 'my name is ' and then your name\nOr even better, add this phone number to your account on", [],[source])
            except:
                pass
        else:
            if "@bot" in message.lower():
                name = signal.getContactName(source)
                
                #signal.sendGroupMessageReaction('U+1F642',False,source,timestamp,groupID)
                signal.sendGroupMessage(bot_answer(message.replace('@bot','').strip(),'group',groupID,name), [], groupID)
                print_to_log("Bot answer send to group")
                
            #Empty message in a group so most likely there has been added or removed a group member
            elif message.replace('@bot','').strip() == "":
                print_to_log("Checking for new members")
                
                global stored_groupmembers
                groupname = signal.getGroupName(groupID)
                
                current_groupmembers = signal.getGroupMembers(groupID)
                new_groupmembers = set(current_groupmembers)-set(stored_groupmembers[groupname])        

                #loop over new members
                for new_groupmember in new_groupmembers:
                    print_to_log("new group member: "+new_groupmember)
                    name = findName(source)
                    
                    #send personal message
                    signal.sendMessage("Hi "+name+ ",\n\nI saw you are new in the "+groupname+". I am the bot.\n\n I can sent you the daily prayer request if you want. Just send me 'schedule prayer' and then the time you want it.\n\nSend 'help' to see what else I can do", [],new_groupmembers)
                    
                    #send group message
                    signal.sendGroupMessage("Welcome "+name+"!", [], groupID)

                #update the list
                stored_groupmembers[groupname] = signal.getGroupMembers(groupID)
                
                print_to_log("Finished adding new members")

    return

#function for receipt received
def receiptReceived(timestamp, source):
    print_to_log('receiptReceived:',timestamp,source) 
 
def checkwebsite():    
    #Every 900 seconds (15 minutes)
    GLib.timeout_add(900000, checkwebsite)
    
    #Check if there are prayers to be send
    send_prayer_message()
    
    print_to_log('Checking for website messages')
    
    notifications = get_websiteinfo('notifications')
    
    if notifications:
        print_to_log('There are notifications')
        for recipient, messages in notifications.items():
            try:
                for message in messages:
                    image = message[1]
                    #create a temp image
                    if image != "":
                        print_to_log('Downloading image to attach to the message')
                        decodeit = open('/home/signal-cli/tmp.jpg', 'wb') 
                        decodeit.write(base64.b64decode((image))) 
                        decodeit.close()
                        image = ['/home/signal-cli/tmp.jpg']
                    else:
                        image = []
                        
                    if recipient.lower() == 'all':
                        print_to_log('Sending website message to the group')
                        signal.sendGroupMessage(message[0], image, groupid_array)
                        print_to_log('Finished sending website message to the group')
                    else:
                        print_to_log('Sending website message to '+str(recipient))
                        signal.sendMessage(message[0], image,[recipient])
                        print_to_log('Finished sending website message to '+str(recipient))
                    
                    #remove temp image
                    os.remove('/home/signal-cli/tmp.jpg')
                    print_to_log('Checking for messages9')
            except:
                pass
    d1 = datetime.datetime.now() + datetime.timedelta(minutes=15)
    if d1.minute < 10:
        minute = '%02d' % d1.minute
    else:
        minute = str(d1.minute)
    print_to_log('Checking for messages finished, will check again around '+str(d1.hour)+":"+minute)
    
    
def send_prayer_message():
    now = datetime.datetime.now()
    #if first quarter of the hour
    if now.minute >= 0 and now.minute < 15:
        print_to_log('Getting prayermessage')
        prayermessage = get_websiteinfo('prayermessage')
        
        ng_holidays = holidays.NG()
        if now in ng_holidays:
            prayermessage += "\n\nHappy "+ng_holidays.get(now)

        with con:
            data = con.execute("SELECT * FROM RECIPIENT WHERE hour = "+str(now.hour))
            for recipient in data:
                print_to_log(str(recipient))
                if recipient[1] == 'group':
                    signal.sendGroupMessage("Good "+get_part_of_day()+",\n\n"+prayermessage, [], json.loads(recipient[3]))
                else:
                    try:
                        signal.sendMessage("Good "+get_part_of_day()+" "+recipient[2]+",\n\n"+prayermessage, [],[recipient[3]])
                    except:
                        pass
                        
        print_to_log('Getting prayermessage finished')
    return
    
#################################################
# CODE
#################################################
    
bus = SystemBus()
loop = GLib.MainLoop()

signal = bus.get('org.asamk.Signal')

signal.onMessageReceived = msgRcv

#Store current groupmembers add boot
GroupIds = signal.getGroupIds()
stored_groupmembers = {}
for GroupID in GroupIds:
    stored_groupmembers[signal.getGroupName(GroupID)] = signal.getGroupMembers(GroupID)


#signal.onReceiptReceived = receiptReceived

#send_prayer_message()

#CHeck for website messages
checkwebsite()
loop.run()
