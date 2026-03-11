from openai import OpenAI
from typing import Optional, List
from app.core.config import settings


def get_ai_client() -> OpenAI:
    """
    DeepSeek API 完全兼容 OpenAI SDK
    申请地址: platform.deepseek.com（国内直接访问）
    """
    return OpenAI(
        api_key=settings.DEEPSEEK_API_KEY,
        base_url=settings.DEEPSEEK_BASE_URL,
    )


def _chat(messages: list, temperature: float = 0.7) -> str:
    if not settings.DEEPSEEK_API_KEY:
        return "[AI功能未配置：请在.env中设置DEEPSEEK_API_KEY]"
    try:
        client = get_ai_client()
        response = client.chat.completions.create(
            model=settings.DEEPSEEK_MODEL,
            messages=messages,
            temperature=temperature,
        )
        return response.choices[0].message.content
    except Exception as e:
        return f"[AI服务暂时不可用: {str(e)}]"


def generate_student_report(
    student_name: str,
    year_group: str,
    attendance_rate: float,
    grade_summary: dict,
    behaviour_notes: Optional[str] = None,
) -> str:
    """AI生成学生进度报告（中文叙述性评语）"""
    grade_text = "\n".join(
        [f"- {k}: {v}" for k, v in grade_summary.items()]
    )
    behaviour_section = f"\n行为记录：{behaviour_notes}" if behaviour_notes else ""

    messages = [
        {
            "role": "system",
            "content": (
                "你是一位经验丰富的教育工作者，请根据学生数据生成专业、温暖、具有建设性的学生进度报告。"
                "报告应该：1）客观反映学生表现 2）指出优势和待改进之处 3）给出具体建议 4）语气积极鼓励。"
                "报告长度200-400字，使用中文。"
            ),
        },
        {
            "role": "user",
            "content": (
                f"请为以下学生生成进度报告：\n\n"
                f"学生姓名：{student_name}\n"
                f"年级：{year_group}\n"
                f"出勤率：{attendance_rate:.1f}%\n"
                f"各科成绩：\n{grade_text}"
                f"{behaviour_section}"
            ),
        },
    ]
    return _chat(messages, temperature=0.8)


def identify_at_risk_students(students_data: List[dict]) -> List[dict]:
    """
    识别学习风险学生
    students_data: [{"name": str, "attendance_rate": float, "grade_avg": float, "trend": str}]
    """
    if not students_data:
        return []

    data_text = "\n".join(
        [
            f"- {s['name']}: 出勤率{s.get('attendance_rate', 0):.1f}%, "
            f"平均分{s.get('grade_avg', 0):.1f}, 趋势{s.get('trend', '稳定')}"
            for s in students_data
        ]
    )

    messages = [
        {
            "role": "system",
            "content": (
                "你是学校数据分析师。根据学生的出勤率、成绩和趋势数据，"
                "识别需要重点关注的学生，并给出风险等级和建议。"
                "以JSON格式返回，格式：[{\"name\": str, \"risk_level\": \"高/中/低\", \"reason\": str, \"suggestion\": str}]"
            ),
        },
        {
            "role": "user",
            "content": f"请分析以下学生数据，识别风险学生：\n\n{data_text}",
        },
    ]

    result = _chat(messages, temperature=0.3)

    # 解析JSON响应
    import json, re
    try:
        json_match = re.search(r"\[.*\]", result, re.DOTALL)
        if json_match:
            return json.loads(json_match.group())
    except Exception:
        pass

    return [{"name": "解析失败", "risk_level": "未知", "reason": result, "suggestion": "请人工审核"}]


def analyze_grade_trends(course_name: str, grade_data: List[dict]) -> str:
    """
    成绩趋势分析
    grade_data: [{"assessment": str, "class_avg": float, "date": str}]
    """
    data_text = "\n".join(
        [f"- {g['assessment']} ({g.get('date', '')}): 班级平均分 {g.get('class_avg', 0):.1f}" for g in grade_data]
    )

    messages = [
        {
            "role": "system",
            "content": "你是教育数据分析师，请分析班级成绩趋势，指出规律、问题和改进建议。用中文回答，200字以内。",
        },
        {
            "role": "user",
            "content": f"课程：{course_name}\n\n成绩数据：\n{data_text}\n\n请分析成绩趋势并给出建议。",
        },
    ]
    return _chat(messages, temperature=0.5)


def detect_attendance_anomalies(class_name: str, attendance_data: List[dict]) -> str:
    """
    考勤异常分析
    attendance_data: [{"student_name": str, "attendance_rate": float, "recent_absences": int}]
    """
    anomalies = [s for s in attendance_data if s.get("attendance_rate", 100) < 80]
    if not anomalies:
        return "当前班级考勤状况良好，无明显异常。"

    data_text = "\n".join(
        [
            f"- {s['student_name']}: 出勤率{s.get('attendance_rate', 0):.1f}%, "
            f"近期缺勤{s.get('recent_absences', 0)}次"
            for s in anomalies
        ]
    )

    messages = [
        {
            "role": "system",
            "content": "你是学校管理员助手，请分析考勤异常情况，指出需要关注的学生并给出处理建议。用中文简洁回答。",
        },
        {
            "role": "user",
            "content": f"班级：{class_name}\n\n以下学生出勤率偏低：\n{data_text}\n\n请给出分析和建议。",
        },
    ]
    return _chat(messages, temperature=0.5)


def suggest_timetable_optimization(schedule_data: dict) -> str:
    """
    课表优化建议
    schedule_data: {"conflicts": list, "constraints": list, "classes": list}
    """
    conflicts = schedule_data.get("conflicts", [])
    if not conflicts:
        return "当前课表没有检测到明显冲突，安排合理。"

    conflicts_text = "\n".join([f"- {c}" for c in conflicts])
    messages = [
        {
            "role": "system",
            "content": "你是教务排课专家，请分析课表冲突并提供具体的调整建议。用中文回答，条理清晰。",
        },
        {
            "role": "user",
            "content": f"发现以下课表冲突：\n{conflicts_text}\n\n请提供调整建议。",
        },
    ]
    return _chat(messages, temperature=0.4)


def ai_teacher_chat(question: str, context: Optional[str] = None) -> str:
    """AI教学助手对话"""
    system_content = (
        "你是一个专业的教育管理助手，帮助教师解答关于教学管理、学生辅导、课程安排等问题。"
        "用中文回答，简洁专业，给出实用建议。"
    )
    if context:
        system_content += f"\n\n当前上下文：{context}"

    messages = [
        {"role": "system", "content": system_content},
        {"role": "user", "content": question},
    ]
    return _chat(messages, temperature=0.7)
