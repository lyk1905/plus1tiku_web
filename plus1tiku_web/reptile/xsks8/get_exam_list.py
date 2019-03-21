#! /usr/bin/env python
# -*- coding: utf-8 -*-

import urllib
import urllib2
import time
import json
import os
import sys

reload(sys)
sys.setdefaultencoding('utf-8')

class ClassInfo:
    def __init__(self, class_id, class_name):
        self.class_id = class_id
        self.class_name = class_name 


class SubClassInfo:
    def __init__(self, class_info, subclass_id, subclass_name):
        self.class_id = class_info.class_id
        self.class_name = class_info.class_name
        self.subclass_id = subclass_id
        self.subclass_name = subclass_name


class CourseInfo:
    def __init__(self, class_id, subclass_id, course_id, course_name):
        self.class_id = class_id
        self.subclass_id = subclass_id
        self.course_id = course_id
        self.course_name = course_name


class ExamInfo:
    def __init__(self, course_info, chapter_id, exam_name):
        self.class_id = course_info.class_id
        self.subclass_id = course_info.subclass_id
        self.course_id = course_info.course_id
        self.course_name = course_info.course_name
        self.chapter_id = chapter_id
        self.exam_name = exam_name


class ExamDetail:
    class QuestionType:
        def __init__(self):
            self.quesTypeDesc = str()
            self.showType = str()
            self.index = 0
            self.typeId = 0

    class QuestionItem:
        def __init__(self):
            self.subjectid = 0
            self.courseid = 0
            self.subjectType = 0
            self.index = 0
            self.score = 0
            self.title = str()
            self.question = str()
            self.rightAnswer = str()
            self.answerNum = 0
            self.analysis = str()
            self.typeId = 0
            self.showType = str()

    def __init__(self, exam_info):
        self.examName = exam_info.exam_name
        self.courseName = exam_info.course_name
        self.types = list()     # 题目类型
        self.items = list()     # 题目

def urlOpen(request):
    response = None
    try:
        response = urllib2.urlopen(request)
    except urllib2.HTTPError, e:
        print e.code
    except urllib2.URLError, e:
        print e.reason
    return response

def dealExamDetail(source_data):
    detail_data = dict()



    detail_data_json = json.dumps(detail_data)
    return detail_data_json

# TODO 后续需要改成通过配置文件读取账号密码
headers = {"User-Agent": "Mozilla/5.0 (Macintosh; Intel Mac OS X 10_13_6) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/73.0.3683.75 Safari/537.36",
           "Referer": "http://gy.xsks8.com/"}
login_data = {"phone": "18218401844", "loginpassword": "123456"}
url_encode_data = urllib.urlencode(login_data)
url = "http://gy.xsks8.com/mtweb//mt/login"
request = urllib2.Request(url, url_encode_data, headers)

response = urlOpen(request)

set_cookie_data = response.headers.dict["set-cookie"]
# get session
tmp_vec = set_cookie_data.split(';')
session = str()
for tmp in tmp_vec:
    if "SESSION" in tmp:
        session = tmp.strip(' ')
headers["Cookie"] = session

# get my course
# http://gy.xsks8.com/mtweb/mt/course/query/mycourse?_=1552809717726

# get all class id
# http://gy.xsks8.com/mtweb/mt/courseclass/allList?_=1552809717727
get_all_class_url = "http://gy.xsks8.com/mtweb/mt/courseclass/allList?_=" + str(time.time() * 100)
get_all_class_request = urllib2.Request(get_all_class_url, headers=headers)
get_all_class_response = urlOpen(get_all_class_request)

# deal all class id list
all_class_info_dict = json.loads(get_all_class_response.read())
class_info_dict = dict()
for item in all_class_info_dict["ccList"]:
    class_info = ClassInfo(item["id"], item["name"])
    class_info_dict[item["id"]] = class_info

# class id = 6 是财务会计
# deal subclass info
all_subclass_info = dict()   # key is class id, value is list of sub class id
for class_id in class_info_dict.keys():
    all_subclass_info[class_id] = list()

for item in all_class_info_dict["cscList"]:
    if int(item["pId"] not in all_subclass_info.keys()):
        continue

    class_id = int(item["pId"])
    class_info = class_info_dict[class_id]
    subclass_id = int(item["id"])
    subclass_name = item["name"]
    subclass_info = SubClassInfo(class_info, subclass_id, subclass_name)
    all_subclass_info[class_id].append(subclass_info)

# get detail course list
# http://gy.xsks8.com/mtweb//mt/course/6/9/list?_=1552817299558
course_info_list = list()
for class_id, subclass_info_list in all_subclass_info.items():
    # TODO 先拉取财务会计的题目
    if class_id != 6:
        continue

    for subclass_info in subclass_info_list:
        # TODO 先拉取初级会计的题目
        if subclass_info.subclass_id != 9:
            continue

        course_list_url = "http://gy.xsks8.com/mtweb//mt/course/%s/%s/list?_=%s" % (class_id, subclass_info.subclass_id, str(time.time() * 100))
        course_list_request = urllib2.Request(course_list_url, headers=headers)
        course_list_response = urlOpen(course_list_request)

        course_list_tmp = json.loads(course_list_response.read())

        for item in course_list_tmp:
            course_info = CourseInfo(class_id, item["isubclassid"], item["icourseid"], item["ccoursename"])
            course_info_list.append(course_info)

# get exam list
# http://gy.xsks8.com/mtweb//mt/chaptertree/1/104?_=1552821659699

exam_list = list()
for course_info in course_info_list:
    exam_list_url = "http://gy.xsks8.com/mtweb//mt/chaptertree/%s/%s?_=%s" % (1, course_info.course_id, str(time.time() * 100))
    exam_list_request = urllib2.Request(exam_list_url, headers=headers)
    exam_list_response = urlOpen(exam_list_request)

    exam_list_tmp = json.loads(exam_list_response.read())

    for item in exam_list_tmp:
        if item["pId"] != 0:
            exam_info = ExamInfo(course_info, item["chapterId"], item["name"])
            exam_list.append(exam_info)


# get single exam
# http://gy.xsks8.com/mtweb/mt/chapterpractise/104/1/87264?chaptername=2019%25E5%25B9%25B4%25E4%25B8%25AD%25E7%25BA%25A7%25E4%25BC%259A%25E8%25AE%25A1%25E8%2580%2583%25E8%25AF%2595%25E3%2580%258A%25E4%25B8%25AD%25E7%25BA%25A7%25E7%25BB%258F%25E6%25B5%258E%25E6%25B3%2595%25E3%2580%258B%25E6%25A8%25A1%25E6%258B%259F%25E8%25AF%2595%25E5%258D%25B7%25EF%25BC%25881%25EF%25BC%2589
for exam in exam_list:
    tmp_data = dict()
    tmp_data["chaptername"] = exam.exam_name
    data = urllib.urlencode(tmp_data)
    exam_detail_url = "http://gy.xsks8.com/mtweb/mt/chapterpractise/%s/1/%s?%s" % (exam.course_id, exam.chapter_id, data)
    exam_detail_request = urllib2.Request(exam_detail_url, headers=headers)
    exam_detail_response = urlOpen(exam_detail_request)

    file_path = os.path.join(os.path.abspath('.'), "exam_list/%s" % exam.course_name)
    if not os.path.exists(file_path):
        os.makedirs(file_path)
    file_name = os.path.join(file_path, exam.exam_name)
    file = open(file_name, "w+")
    file.write(exam_detail_response.read())
