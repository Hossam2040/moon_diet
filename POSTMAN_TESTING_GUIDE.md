# دليل اختبار API باستخدام Postman

## إعداد Postman

### 1. استيراد Collection
1. افتح Postman
2. اضغط على "Import" في الأعلى
3. اختر ملف `postman_collection.json`
4. اضغط "Import"

### 2. استيراد Environment
1. في Postman، اضغط على "Environments" في الشريط الجانبي
2. اضغط "Import"
3. اختر ملف `postman_environment.json`
4. اضغط "Import"
5. تأكد من اختيار "Moon Diet - Local Environment" في القائمة المنسدلة

## خطوات الاختبار

### الخطوة 1: تسجيل الدخول
1. اذهب إلى "Authentication" > "Login User"
2. اضغط "Send"
3. انسخ `token` من الاستجابة
4. اذهب إلى Environment variables
5. ضع الـ token في متغير `auth_token`

### الخطوة 2: الحصول على Menu Items
1. اذهب إلى "Menu Items" > "Get Menu Items"
2. اضغط "Send"
3. انسخ `id` لأي عنصرين من القائمة
4. ضعهم في متغيرات `menu_item_id_1` و `menu_item_id_2`

### الخطوة 3: الحصول على Meal Plans
1. اذهب إلى "Meal Plans" > "Get Meal Plans"
2. اضغط "Send"
3. انسخ `id` لأي خطة وجبات
4. ضعه في متغير `meal_plan_id`

### الخطوة 4: الحصول على Meal Plan Variants
1. اذهب إلى "Meal Plans" > "Get Meal Plan Items"
2. اضغط "Send"
3. انسخ `variant_id` من الاستجابة
4. ضعه في متغير `meal_plan_variant_id`

### الخطوة 5: إنشاء Subscription
1. اذهب إلى "Subscriptions" > "Get Quote"
2. اضغط "Send" أولاً للحصول على عرض سعر
3. اذهب إلى "Create Subscription"
4. اضغط "Send"
5. انسخ `id` من الاستجابة
6. ضعه في متغير `subscription_id`

### الخطوة 6: اختبار إضافة الوجبات

#### اختبار صحيح:
1. اذهب إلى "Subscription Meals" > "Get Subscription Meals"
2. اضغط "Send" لرؤية الوجبات الحالية (ستكون فارغة)
3. اذهب إلى "Set Subscription Meals - Valid"
4. اضغط "Send"
5. يجب أن تحصل على `{"saved": true}`
6. ارجع إلى "Get Subscription Meals" لرؤية الوجبات المضافة

#### اختبارات الأخطاء:

**اختبار Day Index سلبي:**
1. اذهب إلى "Set Subscription Meals - Invalid Day Index"
2. اضغط "Send"
3. يجب أن تحصل على `{"message": "invalid_day_index"}`

**اختبار Day Index خارج النطاق:**
1. اذهب إلى "Set Subscription Meals - Day Index Out of Range"
2. اضغط "Send"
3. يجب أن تحصل على `{"message": "invalid_day_index"}`

**اختبار Menu Item غير صحيح:**
1. اذهب إلى "Set Subscription Meals - Invalid Menu Item"
2. اضغط "Send"
3. يجب أن تحصل على خطأ validation

**اختبار عدد الوجبات خاطئ:**
1. اذهب إلى "Set Subscription Meals - Wrong Total Count"
2. اضغط "Send"
3. يجب أن تحصل على `{"message": "invalid_total_meals_count", "expected": 14}`

**اختبار بدون مصادقة:**
1. اذهب إلى "Set Subscription Meals - Unauthorized"
2. اضغط "Send"
3. يجب أن تحصل على `401 Unauthorized`

## ملاحظات مهمة

1. **تأكد من تشغيل الخادم:** `php artisan serve`
2. **تأكد من قاعدة البيانات:** يجب أن تكون البيانات موجودة
3. **تحديث المتغيرات:** بعد كل عملية إنشاء، حدث المتغيرات المناسبة
4. **الترتيب مهم:** يجب تنفيذ الخطوات بالترتيب المذكور

## استكشاف الأخطاء

### خطأ 404
- تأكد من صحة `subscription_id`
- تأكد من أن الاشتراك يخص المستخدم المسجل دخوله

### خطأ 422
- تأكد من صحة `menu_item_id`
- تأكد من أن العنصر مسموح في خطة الوجبات
- تأكد من عدد الوجبات المطلوب

### خطأ 401
- تأكد من صحة `auth_token`
- تأكد من تسجيل الدخول أولاً
