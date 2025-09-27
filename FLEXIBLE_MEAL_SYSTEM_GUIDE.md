# دليل النظام المرن لإدارة الوجبات

## المميزات الجديدة:

### 1. إضافة وجبات تدريجية
- العميل يقدر يضيف الوجبات تدريجياً مش مرة واحدة
- مش لازم يسجل الشهر كله مرة واحدة

### 2. قيود التوقيت (3 أيام مقدماً)
- لازم يكون قبل التاريخ المطلوب بـ 3 أيام على الأقل
- لو عاوز وجبة يوم 10 يناير، لازم تسجلها قبل 7 يناير

### 3. إمكانية التعديل والحذف
- تقدر تعدل أو تحذف الوجبات قبل 3 أيام من التاريخ

## الـ APIs الجديدة:

### 1. إضافة وجبات (تدريجية):
```
POST http://127.0.0.1:8000/api/subscriptions/{id}/meals
Authorization: Bearer YOUR_TOKEN
Content-Type: application/json

{
    "selections": [
        {
            "day_index": 5,
            "menu_item_id": 1
        },
        {
            "day_index": 5,
            "menu_item_id": 2
        }
    ]
}
```

### 2. حذف وجبة معينة:
```
DELETE http://127.0.0.1:8000/api/subscriptions/{id}/meals
Authorization: Bearer YOUR_TOKEN
Content-Type: application/json

{
    "day_index": 5,
    "menu_item_id": 1
}
```

### 3. عرض الوجبات مع التواريخ:
```
GET http://127.0.0.1:8000/api/subscriptions/{id}/meals
Authorization: Bearer YOUR_TOKEN
```

## أمثلة للاختبار:

### مثال 1: إضافة وجبات لأيام مختلفة
```json
{
    "selections": [
        {
            "day_index": 3,
            "menu_item_id": 1
        },
        {
            "day_index": 3,
            "menu_item_id": 2
        },
        {
            "day_index": 7,
            "menu_item_id": 1
        },
        {
            "day_index": 7,
            "menu_item_id": 2
        }
    ]
}
```

### مثال 2: إضافة وجبة واحدة فقط
```json
{
    "selections": [
        {
            "day_index": 10,
            "menu_item_id": 3
        }
    ]
}
```

## رسائل الخطأ الجديدة:

### 1. عدم كفاية الإشعار المسبق:
```json
{
    "message": "insufficient_advance_notice",
    "target_date": "2024-01-05",
    "minimum_advance_days": 3,
    "days_remaining": 1
}
```

### 2. الوجبة غير موجودة:
```json
{
    "message": "meal_not_found"
}
```

## استجابة عرض الوجبات الجديدة:

```json
{
    "subscription": {
        "id": 1,
        "start_date": "2024-01-01",
        "end_date": "2024-01-31",
        "total_meals": 60,
        "meals_per_day": 2,
        "days": 30,
        "status": "active"
    },
    "meals": [
        {
            "id": 1,
            "day_index": 3,
            "menu_item_id": 1,
            "actual_date": "2024-01-04",
            "can_modify": true,
            "item": {
                "id": 1,
                "name_en": "Grilled Chicken",
                "name_ar": "دجاج مشوي"
            }
        }
    ],
    "summary": {
        "total_selected": 1,
        "remaining": 59
    }
}
```

## نصائح للاختبار:

1. **ابدأ بوجبات لأيام بعيدة** (أكثر من 3 أيام)
2. **جرب إضافة وجبات تدريجياً** مش مرة واحدة
3. **تأكد من التواريخ** في الاستجابة
4. **جرب حذف وجبة** بعد إضافتها
5. **اختبر القيود الزمنية** بجرب إضافة وجبة ليوم قريب

## مثال كامل للاختبار:

### الخطوة 1: عرض الوجبات الحالية
```
GET /api/subscriptions/1/meals
```

### الخطوة 2: إضافة وجبات لأيام مختلفة
```
POST /api/subscriptions/1/meals
{
    "selections": [
        {"day_index": 5, "menu_item_id": 1},
        {"day_index": 5, "menu_item_id": 2},
        {"day_index": 10, "menu_item_id": 3}
    ]
}
```

### الخطوة 3: عرض الوجبات المحدثة
```
GET /api/subscriptions/1/meals
```

### الخطوة 4: حذف وجبة
```
DELETE /api/subscriptions/1/meals
{
    "day_index": 5,
    "menu_item_id": 1
}
```

### الخطوة 5: عرض النتيجة النهائية
```
GET /api/subscriptions/1/meals
```
