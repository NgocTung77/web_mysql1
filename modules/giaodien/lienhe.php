<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Liên hệ</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            margin: 0;
            padding: 0;
            background: #f4f4f4;
        }

        .contact-container {
            max-width: 1200px;
            margin: 20px auto;
            padding: 20px;
            background: #fff;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.2);
            border-radius: 5px;
        }

        h1 {
            text-align: center;
            color: #333;
        }

        .contact-info {
            display: flex;
            justify-content: space-between;
            margin: 20px 0;
        }

        .contact-info div {
            width: 30%;
            text-align: center;
        }

        .contact-info i {
            font-size: 40px;
            color: #007BFF;
            margin-bottom: 10px;
        }

        .contact-form {
            margin-top: 20px;
        }

        .contact-form form {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }

        .contact-form input, 
        .contact-form textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
        }

        .contact-form textarea {
            resize: none;
        }

        .contact-form button {
            padding: 10px;
            background: #007BFF;
            color: #fff;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
        }

        .contact-form button:hover {
            background: #0056b3;
        }

        iframe {
            width: 100%;
            height: 300px;
            border: none;
            margin-top: 20px;
        }
    </style>
</head>
<body>
    <div class="contact-container">
        <h1>Liên hệ với chúng tôi</h1>

        <!-- Thông tin liên hệ -->
        <div class="contact-info">
            <div>
                <i class="fas fa-map-marker-alt"></i>
                <h3>Địa chỉ</h3>
                <p>123 Đường ABC, Quận 1, TP. Cân Thơ</p>
            </div>
            <div>
                <i class="fas fa-phone"></i>
                <h3>Số điện thoại</h3>
                <p>0123 456 789</p>
            </div>
            <div>
                <i class="fas fa-envelope"></i>
                <h3>Email</h3>
                <p>tungngoc2308@gmail.com</p>
            </div>
        </div>

        <!-- Biểu mẫu liên hệ -->
        <div class="contact-form">
            <form action="modules/right/xuly_lienhe.php" method="POST">
                <input type="text" name="name" placeholder="Họ và tên" required>
                <input type="email" name="email" placeholder="Email" required>
                <input type="text" name="subject" placeholder="Tiêu đề">
                <textarea name="message" rows="5" placeholder="Nội dung" required></textarea>
                <button type="submit">Gửi liên hệ</button>
            </form>
        </div>

        <!-- Google Map -->
        <iframe 
            src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3929.1078876651777!2d105.72025667479366!3d10.007946490097849!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x31a08903d92d1d0d%3A0x2c147a40ead97caa!2sNam%20Can%20Tho%20University!5e0!3m2!1sen!2sus!4v1733415843777!5m2!1sen!2sus" 
            allowfullscreen="" 
            loading="lazy">
        </iframe>
    </div>
</body>
</html>
