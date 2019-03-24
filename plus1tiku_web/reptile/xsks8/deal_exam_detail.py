#! /usr/bin/env python
# -*- coding: utf-8 -*-

import json
import os
import sys

reload(sys)
sys.setdefaultencoding('utf-8')

class ExamDetail:
    class ExamDetailEncoder(json.JSONEncoder):
        def default(self, obj):
            if isinstance(obj, ExamDetail):
                return obj.toDict()
            return json.JSONEncoder.default(self, obj)

    class QuestionType:
        def __init__(self):
            self.quesTypeDesc = str()
            self.showType = str()
            self.index = 0
            self.typeId = 0

        @staticmethod
        def parse(statis):
            question_type = ExamDetail.QuestionType()

            question_type.showType = statis.type_name
            question_type.index = statis.index
            question_type.typeId = statis.type_id

            if question_type.showType == "单项选择题":
                question_type.quesTypeDesc = '本类题共%d小题，每小题%s分，共%d分。每小题备选答案中，只有一个符合题意的正确答案。多选、错选、不选均不得分。' % (statis.item_count, statis.score, int(statis.item_count * float(statis.score)))
            elif question_type.showType == "多项选择题":
                question_type.quesTypeDesc = '本类题共%d小题，每小题%s分，共%d分。每小题备选答案中，有两个或两个以上符合题意的正确答案。多选、少选、错选、不选均不得分。' % (statis.item_count, statis.score, int(statis.item_count * float(statis.score)))
            elif question_type.showType == "判断题":
                question_type.quesTypeDesc = '本类题共%d小题，每小题%s分，共%d分。请判断每小题的表述是否正确。每小题答题正确的得1分，答题错误的扣0.5分，不答题的不得分也不扣分。本类题最低得分为零分。' % (statis.item_count, statis.score, int(statis.item_count * float(statis.score)))
            elif question_type.showType == "不定项选择题":
                question_type.quesTypeDesc = '本类题共%d小题，每小题%s分，共%d分。每小题备选答案中，有一个或一个以上符合题意的正确答案。每小题全部选对得满分，少选得相应分值，多选、错选、不选均不得分。请使用计算机鼠标在计算机答题界面上点击试题答案备选项前的按钮“□”作答。' % (statis.item_count, statis.score, int(statis.item_count * float(statis.score)))
            else:
                raise RuntimeError("未知题型")

            return question_type

        def toDict(self):
            tmp = dict()
            tmp["quesTypeDesc"] = self.quesTypeDesc
            tmp["showType"] = self.showType
            tmp["index"] = self.index
            tmp["typeId"] = self.typeId

            return tmp

    class TypeStatistics:
        def __init__(self):
            self.index = 0
            self.item_count = 0
            self.score = str()
            self.type_name = ""
            self.type_id = 0

        def __cmp__(self, other):
            return cmp(self.index, other.index)

        def add(self):
            self.item_count = self.item_count + 1

        @staticmethod
        def gen(question_item, index):
            type_statistics = ExamDetail.TypeStatistics()
            type_statistics.index = index
            type_statistics.type_name = question_item.showType
            type_statistics.score = question_item.score
            type_statistics.item_count = 1 
            type_statistics.type_id = question_item.typeId

            return type_statistics

    class QuestionItem:
        def __init__(self):
            self.subjectid = 0
            self.courseid = 0
            self.subjectType = 0
            self.index = 0
            self.score = str()
            self.title = str()
            self.question = str()
            self.rightAnswer = str()
            self.answerNum = 0
            self.analysis = str()
            self.typeId = 0         # ??
            self.showType = str()

        def toDict(self):
            tmp = dict()
            tmp["subjectid"] = self.subjectid
            tmp["courseid"] = self.courseid
            tmp["subjectType"] = self.subjectType
            tmp["index"] = self.index
            tmp["score"] = self.score
            tmp["title"] = self.title
            tmp["question"] = self.question
            tmp["rightAnswer"] = self.rightAnswer
            tmp["answerNum"] = self.answerNum
            tmp["analysis"] = self.analysis
            tmp["typeId"] = self.typeId
            tmp["showType"] = self.showType

            return tmp

        @staticmethod
        def parse(item):
            question_item = ExamDetail.QuestionItem()

            question_item.subjectid = item["isubjectid"]
            question_item.subjectType = item["isubjecttype"]
            question_item.courseid = item["icourseid"]
            question_item.index = item["iindex"]
            question_item.score = str(item["iscore"])
            question_item.title = item["ctitleStr"].replace("<br />\\n", "\\r\\n").replace("<br />", "")
            question_item.question = item["cquestionStr"].replace("<br />\\n", "\\r\\n").replace("<br />", "")
            
            if "canswer" in item.keys():
                question_item.rightAnswer = item["canswer"]
            
            question_item.analysis = item["cdescriptionStr"].replace("<br />\\n", "\\r\\n").replace("<br />", "")
            question_item.typeId = item["isubjecttype"]
            question_item.showType = item["csubjecttype"]
            question_item.answerNum = item["ianswercount"]

            return question_item

    def __init__(self, exam_info=None):
        if exam_info is not None:
            self.examName = exam_info.exam_name
            self.courseName = exam_info.course_name
        else:
            self.examName = "test"
            self.courseName = "test"
        self.types = list()     # 题目类型
        self.items = list()     # 题目
        self.statis_tmp = dict()   # 统计使用的字典

    def toDict(self):
        tmp = dict()
        tmp["examName"] = self.examName
        tmp["types"] = list()
        tmp["items"] = list()

        for type_item in self.types:
            tmp["types"].append(type_item.toDict())

        for item in self.items:
            tmp["items"].append(item.toDict())

        return tmp

    def statistics(self, question_item):
        if question_item.showType not in self.statis_tmp.keys():
            type_statistics = ExamDetail.TypeStatistics.gen(question_item, len(self.statis_tmp.values())+1)

            self.statis_tmp[question_item.showType] = type_statistics
        else:
            self.statis_tmp[question_item.showType].add()

    def statis2types(self):
        statis_list = self.statis_tmp.values()
        statis_list = sorted(statis_list)

        for statis in statis_list:
            question_type = ExamDetail.QuestionType.parse(statis)
            self.types.append(question_type)

    def parse(self, source_data):
        #detail_data = dict()

        tmp_exam_data = None
        tmp_list = source_data.split("\n")
        
        for tmp in tmp_list:
            if "loaddata" in tmp:
                tmp_exam_data = tmp
                break

        if tmp_exam_data is None:
            print "no loaddata"
            return ""

        tmp_exam_data = tmp_exam_data.strip(';')
        tmp_list = tmp_exam_data.split("=", 1)
        exam_data = tmp_list[1].strip(' ').rstrip('\n').rstrip(';')
        exam_data = exam_data[:-2]
        #print exam_data
        exam_data_json = json.loads(exam_data)
        #print exam_data_json

        for item in exam_data_json:
            #print item
            question_item = ExamDetail.QuestionItem.parse(item)
            self.statistics(question_item)
            self.items.append(question_item)

        self.statis2types()

        detail_data_json = json.dumps(self, cls=ExamDetail.ExamDetailEncoder, ensure_ascii=False, indent=2)
        return detail_data_json


if __name__ == "__main__":
    file_name = sys.argv[1]
    data_file = open(file_name, "r")
    test_data = data_file.read()

    exam_detail = ExamDetail()
    detail_data_json = exam_detail.parse(test_data)

    print detail_data_json

    file_path = os.path.join(os.path.abspath('.'), "exam_list/json_test")
    if not os.path.exists(file_path):
        os.makedirs(file_path)
    file_name = os.path.join(file_path, "test.json")
    file = open(file_name, "w+")
    file.write(detail_data_json)
    file.close()

