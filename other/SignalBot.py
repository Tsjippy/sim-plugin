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
# /usr/local/bin/signal-cli --dbus-system send -m "Message" +2349045252526
# connected to +2349011531222

#copy files from windows: pscp -P 22 "C:\Users\ewald\Downloads\signalbot.db" signal-cli@10.14.27.23:/home/signal-cli/
#copy files from linux: pscp -P 22 signal-cli@10.14.27.23:/home/signal-cli/SignalBot.py D:\


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
import sys


########################################
# VARIABLES
#############################
db = '/home/signal-cli/signal-bot/signalbot.db'

#SIM Nigeria Group ID
groupid = "hzR2JedCcIQ+aRIh/ByziWfPrNY7GNeyvStkZ+tbyB0=" #print(",".join(str(i) for i in base64.b64decode(b"hzR2JedCcIQ+aRIh/ByziWfPrNY7GNeyvStkZ+tbyB0=")))

#test group id
testgroupid = "LOghzl8qt6VO9l7025bJTnnvJGnrjRfAg1rUNAycFEs="

groupid_array = []
for i in base64.b64decode(groupid.encode()):
    groupid_array.append(i)

###################################################
# FUNCTIONS
##################################################

def edit_db(query, data=()):
    con = sl.connect(db)
    cursor=con.cursor()
    cursor.execute(query, data)
    con.commit()
    con.close()
    return cursor.lastrowid

def get_db_data(query):
    con = sl.connect(db)
    cursor=con.cursor()
    data = cursor.execute(query).fetchall()
    con.close()
    return data

# prayer messagesend table
edit_db("""
    CREATE TABLE IF NOT EXISTS OTHER (
        id INTEGER NOT NULL PRIMARY KEY AUTOINCREMENT,
        name TEXT,
        value TEXT
    );
""")

# recipients table
edit_db("""
    CREATE TABLE IF NOT EXISTS RECIPIENT (
        id INTEGER NOT NULL PRIMARY KEY AUTOINCREMENT,
        type TEXT,
        name TEXT,
        number TEXT,
        hour INTEGER
    );
""")

def schedule_prayer_request(type, name, number, hour):
    sql = 'INSERT INTO RECIPIENT (type, name, number, hour) values(?, ?, ?, ?)'
    data = (type, name, str(number), hour)
    
    return edit_db(sql, data)

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

        url = "https://simnigeria.org/wp-json/simnigeria/v1/"+command
        verify = True
        USERNAME = "signalbot"
        PASSWORD = "w6Fn taY7 kbPl jbOO NVUd Fok2"#applicationpassword
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
        print_to_log("Requesting "+command+" timedout, trying again")
        return get_websiteinfo(command)
    except Exception as e:
        print_to_log(command+' failed.')
        error_line = str(e.__traceback__.tb_lineno)
        print('Error on line '+error_line+': '+str(e))
                        
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
            try:
                time        = re.search(r'(\d{1,2}):(\d{1,2})',message)
                hour        = int(time.group(1))
            except Exception as e:
                return "I don't know when to schedule the prayer request for you\nInclude a time in the form of '9AM' or '15:00' in your request"
        else:
            return "I don't know when to schedule the prayer request for you\nInclude a time in the form of '9AM' or '15:00' in your request"
            
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
        data = get_db_data("SELECT * FROM RECIPIENT WHERE number = '"+str(source)+"'")
        #check if there is already an subscription for this source
        if len(data) == 0:                    
            schedule_prayer_request(type, name, source, hour)
                
            return "From now on I will send daily prayer requests to "+text+" around "+str(hour)+" o'clock\n\nHere is the first one:\n"+get_websiteinfo('prayermessage')
        else:
            return "You already have a subscription at "+str(data[0][4])+" o'clock"
    elif 'remove' in msg and 'prayer' in msg:
        if type == 'group':
            source = json.dumps(source)
        data = get_db_data("SELECT * FROM RECIPIENT WHERE number = '"+str(source)+"'")
        
        if data!=None:
            edit_db("DELETE FROM RECIPIENT WHERE number = '"+str(source)+"'")
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
    f = open("/home/signal-cli/signal-bot/signal_bot_log.txt", "a")
    f.write(dt_string+": "+msg+"\n")
    f.close()
    return
    
#run when a signal message is received
def msgRcv (timestamp, source, groupID, message, attachments):
    message = message.replace(u'￼','').strip()
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
        signal.sendReadReceipt(source,[timestamp])
    
        #are we receiving from a group or an person?
        if len(groupID) == 0:
            #Person
            try:
                signal.sendTyping(source,False)
                
                name = findName(source)
                print_to_log("name is: "+name)
                
                answer = bot_answer(message,'person',source,name)
                print_to_log("Answer is: "+answer)
                print_to_log("Sending answer to: "+str(source))
                
                signal.sendMessage(answer, [],[source])
                
                print_to_log("Bot answer send")
                
                if 'my name is' not in message.lower() and name == "":
                    answer = "I do not know you yet, please tell me your name by sending 'my name is ' and then your name\nOr even better, add this phone number to your account on https://simnigeria.org/account/?section=generic"
                    signal.sendMessage(answer, [], [source])
            except Exception as e:
                print_to_log('Sending message to '+str(source)+' failed')
                
                error_line = str(e.__traceback__.tb_lineno)
                print('Error on line '+error_line+': '+str(e))

                if str(e) == 'Must be string, not list':
                    print_to_log('Trying again...')
                    signal.sendMessage(answer, [], source)
                    print_to_log('This attempt was succesfull.')
        else:
            if "@bot" in message.lower() or "mentions" in attachments and attachments['mentions'][0]['recipient'] == signal.getSelfNumber():
                signal.sendGroupTyping(groupID,False)
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
                    try:
                        message = "Hi "+name+ ",\n\nI saw you are new in the "+groupname+". I am the SIM Nigeria bot.\n\n I can sent you the daily prayer request if you want. Just send me 'schedule prayer' and then the time you want it.\n\nSend 'help' to see what else I can do"
                        signal.sendMessage(message, [], [new_groupmember])
                    except Exception as e:
                        print_to_log('Sending message to '+str(new_groupmember)+' failed')
                        
                        error_line = str(e.__traceback__.tb_lineno)
                        print('Error on line '+error_line+': '+str(e))

                        if str(e) == 'Must be string, not list':
                            signal.sendMessage(message, [], new_groupmember)
                    #send group message
                    signal.sendGroupMessage("Welcome "+name+"!", [], groupID)

                #update the list
                stored_groupmembers[groupname] = signal.getGroupMembers(groupID)
                
                print_to_log("Finished adding new members")

    return

#function for receipt received
def receiptReceived(timestamp, source, new1, new2):
    print_to_log('receiptReceived: '+str(timestamp)+'  '+ str(source)+'  '+ str(new1)+'  '+ str(new2)) 
 
def checkwebsite():    
    #Every 300 seconds (5 minutes)
    GLib.timeout_add(300000, checkwebsite)
    
    print_to_log('Checking for website messages')
    
    notifications = get_websiteinfo('notifications')
    if notifications:
        print_to_log('There are notifications')
        for recipient, messages in notifications.items():
            try:
                for message in messages:
                    print_to_log(str(message[0]))
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
                        print_to_log('Sending '+str(message[0])+' to the group')
                        signal.sendGroupMessage(message[0], image, groupid_array)
                        print_to_log('Finished sending website message to the group')
                    else:
                        print_to_log('Sending '+str(message[0])+' to '+str(recipient))
                        try:
                            signal.sendMessage(message[0], image,[recipient])
                        except Exception as e:
                            print_to_log('Sending message to '+str(recipient)+' failed')
                            
                            error_line = str(e.__traceback__.tb_lineno)
                            print('Error on line '+error_line+': '+str(e))
                        
                            if str(e) == 'Must be string, not list':
                                signal.sendMessage(message[0], image, recipient)
                        print_to_log('Finished sending website message to '+str(recipient))
                    
                    #remove temp image
                    if(os.path.isfile('/home/signal-cli/tmp.jpg')):
                        os.remove('/home/signal-cli/tmp.jpg')

                    #if last message
                    if i == len(messages)-1:
                        print_to_log('All messages have been send')
                    else:
                        print_to_log('Going to next message')
            except Exception as e:
                print_to_log('Sending message to '+str(recipient)+' failed')
                error_line = str(e.__traceback__.tb_lineno)
                print('Error on line '+error_line+': '+str(e))
                        

    d1 = datetime.datetime.now() + datetime.timedelta(minutes=5)
    if d1.minute < 10:
        minute = '%02d' % d1.minute
    else:
        minute = str(d1.minute)
    print_to_log('Checking for messages finished, will check again around '+str(d1.hour)+":"+minute)
    
def storelasttime(hour):
    #edit_db('INSERT INTO OTHER (name, value) values(?, ?)', ('prayersend', hour))
    edit_db('UPDATE OTHER set value = ? WHERE name = "prayersend"', [hour])

    return

 
#################################################
# CODE
#################################################
    
bus = SystemBus()
loop = GLib.MainLoop()

signal = bus.get('org.asamk.Signal')

print_to_log('########################')
print_to_log('')
print_to_log('      PROGRAMM STARTED')
print_to_log('')
print_to_log('########################')

#print(dir(signal))
#signal.sendMessage("Good ", [],'+2349045252526')
signal.onMessageReceivedV2 = msgRcv
#signal.onReceiptReceivedV2 = receiptReceived

#Store current groupmembers add boot
GroupIds = signal.getGroupIds()
stored_groupmembers = {}
for GroupID in GroupIds:
    stored_groupmembers[signal.getGroupName(GroupID)] = signal.getGroupMembers(GroupID)


#CHeck for website messages
checkwebsite()
loop.run()
