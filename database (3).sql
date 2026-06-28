-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jun 27, 2026 at 06:59 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `internship_tracker`
--

-- --------------------------------------------------------

--
-- Table structure for table `applications`
--

CREATE TABLE `applications` (
  `id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `job_id` int(11) NOT NULL,
  `cover_letter` text DEFAULT NULL,
  `resume` varchar(255) DEFAULT NULL,
  `status` enum('pending','reviewed','interview','accepted','rejected') DEFAULT 'pending',
  `interview_date` varchar(50) DEFAULT NULL,
  `interview_time` varchar(50) DEFAULT NULL,
  `interview_venue` varchar(255) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `applied_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `applications`
--

INSERT INTO `applications` (`id`, `student_id`, `job_id`, `cover_letter`, `resume`, `status`, `interview_date`, `interview_time`, `interview_venue`, `notes`, `applied_at`) VALUES
(1, 12, 1, 'I am a final-year Computer Science student at UTM with strong PHP and Laravel skills. I have built several web projects and I am eager to contribute to TechCorp real-world systems. I am a fast learner and work well in team environments.', NULL, 'rejected', NULL, NULL, NULL, NULL, '2026-06-23 06:46:20'),
(2, 12, 3, 'Although my primary field is software development, I have a strong interest in UI/UX and have completed online courses in Figma and user research. I believe I can bridge the gap between design and development effectively.', NULL, 'interview', '2026-06-25', '22:44', 'meet', NULL, '2026-06-23 06:46:20'),
(3, 14, 2, 'As a Data Science student with hands-on experience in Python and SQL, I am excited to apply my analytical skills at TechCorp. I have completed projects in data visualisation and predictive modelling that I would love to discuss.', NULL, 'accepted', NULL, NULL, NULL, NULL, '2026-06-23 06:46:20'),
(4, 15, 4, 'I am a graphic design student with a strong portfolio in branding and digital design. Adobe Illustrator and Photoshop are my daily tools, and I would love to bring creative value to the CreativeMinds team.', NULL, 'interview', '', '', '', NULL, '2026-06-22 22:46:20'),
(5, 17, 5, 'Social media is my passion. I have managed accounts for two campus organisations and understand what drives engagement. I am confident I can create compelling content strategies for your agency clients.', NULL, 'reviewed', NULL, NULL, NULL, NULL, '2026-06-23 06:46:20'),
(6, 13, 6, 'With a Finance degree and Bloomberg Terminal experience from university labs, I am well-prepared for the investment intern role at FinanceFirst. I am detail-oriented, analytical, and highly motivated to learn.', NULL, 'accepted', NULL, NULL, NULL, NULL, '2026-06-23 06:46:20'),
(7, 19, 7, 'As an ACCA part-qualified accounting student, I have a strong foundation in financial reporting and bookkeeping. I am meticulous, reliable, and eager to support the FinanceFirst accounting department.', NULL, 'pending', NULL, NULL, NULL, NULL, '2026-06-23 06:46:20'),
(8, 18, 14, 'Civil engineering is my calling. I have completed site visit projects during my studies and I am familiar with AutoCAD and MS Project. I am excited to gain hands-on experience with GreenBuild sustainable projects.', NULL, 'reviewed', NULL, NULL, NULL, NULL, '2026-06-23 06:46:20'),
(9, 21, 16, 'My background in logistics and supply chain management makes me an ideal fit for this role. I have studied freight operations and am proficient in SAP and Excel. I am ready to contribute from day one.', NULL, 'pending', NULL, NULL, NULL, NULL, '2026-06-23 06:46:20'),
(10, 20, 1, 'As a cybersecurity student, I also have strong programming fundamentals. I am interested in understanding secure software development practices and contributing to TechCorp engineering team as a motivated intern.', NULL, 'rejected', NULL, NULL, NULL, NULL, '2026-06-23 06:46:20');

-- --------------------------------------------------------

--
-- Table structure for table `jobs`
--

CREATE TABLE `jobs` (
  `id` int(11) NOT NULL,
  `company_id` int(11) NOT NULL,
  `title` varchar(150) NOT NULL,
  `description` text DEFAULT NULL,
  `location` varchar(100) DEFAULT NULL,
  `field` varchar(100) DEFAULT NULL,
  `status` enum('active','closed') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `allowance` decimal(10,2) DEFAULT 0.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `jobs`
--

INSERT INTO `jobs` (`id`, `company_id`, `title`, `description`, `location`, `field`, `status`, `created_at`, `allowance`) VALUES
(1, 2, 'Software Developer Intern', 'Join our core engineering team to build scalable web applications using PHP and Laravel. You will work alongside senior developers, participate in code reviews, and contribute to real production systems. Responsibilities include feature development, bug fixing, and writing unit tests.', 'Kuala Lumpur', 'Information Technology', 'active', '2026-06-23 06:46:20', 1200.00),
(2, 2, 'Data Analyst Intern', 'Assist our analytics team in collecting, cleaning, and analysing business data to generate actionable insights. You will create dashboards using Power BI and prepare weekly reports for management. Experience with Python or SQL is an advantage.', 'Kuala Lumpur', 'Information Technology', 'active', '2026-06-23 06:46:20', 1000.00),
(3, 2, 'UI/UX Design Intern', 'Work with our product team to design intuitive and visually appealing user interfaces for our web and mobile applications. You will conduct user research, create wireframes, and prototype design solutions using Figma.', 'Petaling Jaya', 'Information Technology', 'active', '2026-06-23 06:46:20', 900.00),
(4, 3, 'Graphic Design Intern', 'Support our creative team in producing visual content for client campaigns, including social media graphics, branding materials, and digital advertisements. Proficiency in Adobe Illustrator and Photoshop is required.', 'Bangsar, Kuala Lumpur', 'Arts & Design', 'active', '2026-06-23 06:46:20', 800.00),
(5, 3, 'Social Media Marketing Intern', 'Manage and grow social media accounts for our agency clients. Responsibilities include content planning, copywriting, scheduling posts, and analysing engagement metrics. Experience with Meta Business Suite is a plus.', 'Bangsar, Kuala Lumpur', 'Marketing', 'active', '2026-06-23 06:46:20', 850.00),
(6, 4, 'Finance & Investment Intern', 'Assist analysts in financial modelling, equity research, and market analysis. You will compile financial reports, monitor portfolio performance, and prepare presentations for client meetings. Knowledge of Bloomberg Terminal is preferred.', 'KLCC, Kuala Lumpur', 'Finance', 'active', '2026-06-23 06:46:20', 1500.00),
(7, 4, 'Accounting Intern', 'Support the accounts department in daily bookkeeping, bank reconciliation, and preparation of financial statements. You will assist with month-end closing processes and liaising with auditors.', 'KLCC, Kuala Lumpur', 'Finance', 'active', '2026-06-23 06:46:20', 1000.00),
(8, 4, 'Risk Management Intern', 'Assist the risk team in identifying and assessing operational and credit risks. Responsibilities include data gathering, maintaining risk registers, and preparing risk assessment reports for senior management.', 'KLCC, Kuala Lumpur', 'Finance', 'active', '2026-06-23 06:46:20', 1100.00),
(9, 4, 'Corporate Banking Intern', 'Rotate across various corporate banking divisions including credit, trade finance, and treasury. You will shadow relationship managers, assist with credit proposals, and conduct industry research.', 'KLCC, Kuala Lumpur', 'Finance', 'closed', '2026-06-23 06:46:20', 1300.00),
(10, 5, 'Healthcare Administration Intern', 'Assist the clinic administration team in managing patient records, scheduling appointments, and handling billing processes. You will also support our digital health initiatives and help maintain our patient management system.', 'Damansara, Selangor', 'Healthcare', 'active', '2026-06-23 06:46:20', 750.00),
(11, 6, 'Software Development Intern (EdTech)', 'Contribute to the development of our e-learning platform by building new features, fixing bugs, and improving system performance. Tech stack: PHP, JavaScript, MySQL. Experience with LMS platforms is a bonus.', 'Shah Alam, Selangor', 'Information Technology', 'active', '2026-06-23 06:46:20', 1000.00),
(12, 6, 'Content Development Intern', 'Create and curate educational content for our online learning modules, including lesson scripts, assessment questions, and learning materials. Strong writing skills and a passion for education are required.', 'Shah Alam, Selangor', 'Education', 'active', '2026-06-23 06:46:20', 800.00),
(13, 6, 'Digital Marketing Intern', 'Plan and execute digital marketing campaigns to promote our e-learning platform. Responsibilities include SEO optimisation, Google Ads management, email marketing, and performance reporting.', 'Shah Alam, Selangor', 'Marketing', 'active', '2026-06-23 06:46:20', 850.00),
(14, 7, 'Civil Engineering Intern', 'Support project engineers in site supervision, quantity surveying, and preparation of engineering drawings. You will visit construction sites, assist in project scheduling, and maintain as-built records.', 'Johor Bahru, Johor', 'Engineering', 'active', '2026-06-23 06:46:20', 900.00),
(15, 7, 'BIM Technician Intern', 'Assist in the creation and management of Building Information Models (BIM) for current construction projects. Proficiency in Autodesk Revit or AutoCAD is required. Training will be provided.', 'Johor Bahru, Johor', 'Engineering', 'active', '2026-06-23 06:46:20', 850.00),
(16, 9, 'Logistics Operations Intern', 'Support the operations team in coordinating freight shipments, managing delivery schedules, and liaising with carriers and customs agents. You will also assist in preparing shipping documentation and resolving delivery issues.', 'Port Klang, Selangor', 'Logistics', 'active', '2026-06-23 06:46:20', 900.00),
(17, 9, 'Supply Chain Analyst Intern', 'Analyse supply chain data to identify bottlenecks and optimise processes. Responsibilities include demand forecasting, supplier performance tracking, and preparing weekly supply chain reports using Excel and SAP.', 'Port Klang, Selangor', 'Logistics', 'active', '2026-06-23 06:46:20', 1000.00),
(18, 10, 'Journalism & Content Intern', 'Research, write, and edit news articles and feature stories for our online portal. You will attend press conferences, conduct interviews, and work under tight deadlines to deliver accurate and engaging content.', 'Cyberjaya, Selangor', 'Media & Communication', 'active', '2026-06-23 06:46:20', 800.00),
(19, 10, 'Video Production Intern', 'Assist in the production of video content including shooting, editing, and post-production. Proficiency in Adobe Premiere Pro or Final Cut Pro is required. Experience with motion graphics is an advantage.', 'Cyberjaya, Selangor', 'Media & Communication', 'active', '2026-06-23 06:46:20', 900.00),
(20, 10, 'Digital Content Strategist Intern', 'Help develop and execute content strategies across our social media platforms and digital channels. You will conduct competitor analysis, plan content calendars, and monitor engagement metrics.', 'Cyberjaya, Selangor', 'Marketing', 'active', '2026-06-23 06:46:20', 850.00);

-- --------------------------------------------------------

--
-- Table structure for table `password_resets`
--

CREATE TABLE `password_resets` (
  `id` int(11) NOT NULL,
  `email` varchar(100) NOT NULL,
  `token` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `reminders`
--

CREATE TABLE `reminders` (
  `id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `app_id` int(11) DEFAULT NULL,
  `task` text NOT NULL,
  `due_date` datetime NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('student','company','admin') NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `bio` text DEFAULT NULL,
  `profile_picture` varchar(255) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `website` varchar(150) DEFAULT NULL,
  `resume` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `approval_status` varchar(50) DEFAULT 'pending',
  `academic_info` text DEFAULT NULL,
  `industry` varchar(100) DEFAULT NULL,
  `skills` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `name`, `email`, `password`, `role`, `phone`, `bio`, `profile_picture`, `description`, `website`, `resume`, `created_at`, `approval_status`, `academic_info`, `industry`, `skills`) VALUES
(1, 'Admin', 'admin@interntrack.com', '5f4dcc3b5aa765d61d8327deb882cf99', 'admin', NULL, NULL, NULL, NULL, NULL, NULL, '2026-06-23 06:46:20', 'approved', NULL, NULL, NULL),
(2, 'TechCorp Sdn Bhd', 'hr@techcorp.com.my', '5f4dcc3b5aa765d61d8327deb882cf99', 'company', '03-1234-5678', NULL, NULL, 'A leading technology company specialising in software development, cloud computing, and AI solutions. We have over 500 employees across Malaysia and Singapore.', 'https://www.techcorp.com.my', NULL, '2026-06-23 06:46:20', 'approved', NULL, 'Information Technology', NULL),
(3, 'CreativeMinds Agency', 'careers@creativeminds.my', '5f4dcc3b5aa765d61d8327deb882cf99', 'company', '03-2345-6789', NULL, NULL, 'Award-winning creative agency offering branding, digital marketing, and content production services to clients across Southeast Asia.', 'https://www.creativeminds.my', NULL, '2026-06-23 06:46:20', 'approved', NULL, 'Marketing & Advertising', NULL),
(4, 'FinanceFirst Bhd', 'internship@financefirst.com.my', '5f4dcc3b5aa765d61d8327deb882cf99', 'company', '03-3456-7890', NULL, NULL, 'One of Malaysia top financial services firms providing investment banking, wealth management, and corporate finance solutions.', 'https://www.financefirst.com.my', NULL, '2026-06-23 06:46:20', 'approved', NULL, 'Finance & Banking', NULL),
(5, 'HealthPlus Medical Group', 'jobs@healthplus.my', '5f4dcc3b5aa765d61d8327deb882cf99', 'company', '03-4567-8901', NULL, NULL, 'A network of modern private clinics and specialist centres focused on delivering high-quality, affordable healthcare across Malaysia.', 'https://www.healthplus.my', NULL, '2026-06-23 06:46:20', 'approved', NULL, 'Healthcare', NULL),
(6, 'EduLearn Solutions', 'hr@edulearn.com.my', '5f4dcc3b5aa765d61d8327deb882cf99', 'company', '03-5678-9012', NULL, NULL, 'An innovative EdTech company developing e-learning platforms, curriculum design tools, and online tutoring services for K-12 and university students.', 'https://www.edulearn.com.my', NULL, '2026-06-23 06:46:20', 'approved', NULL, 'Education & EdTech', NULL),
(7, 'GreenBuild Engineering', 'recruit@greenbuild.my', '5f4dcc3b5aa765d61d8327deb882cf99', 'company', '03-6789-0123', NULL, NULL, 'A civil and structural engineering firm committed to sustainable construction practices, infrastructure development, and green building certification.', 'https://www.greenbuild.my', NULL, '2026-06-23 06:46:20', 'approved', NULL, 'Engineering & Construction', NULL),
(8, 'RetailMax Sdn Bhd', 'people@retailmax.com.my', '5f4dcc3b5aa765d61d8327deb882cf99', 'company', '03-7890-1234', NULL, NULL, 'A fast-growing retail chain with over 80 outlets nationwide, specialising in consumer electronics, home appliances, and lifestyle products.', 'https://www.retailmax.com.my', NULL, '2026-06-23 06:46:20', 'approved', NULL, 'Retail & E-Commerce', NULL),
(9, 'LogiTrans Bhd', 'hr@logitrans.com.my', '5f4dcc3b5aa765d61d8327deb882cf99', 'company', '03-8901-2345', NULL, NULL, 'A comprehensive logistics and supply chain company offering freight forwarding, last-mile delivery, and warehouse management across Malaysia.', 'https://www.logitrans.com.my', NULL, '2026-06-23 06:46:20', 'approved', NULL, 'Logistics & Supply Chain', NULL),
(10, 'MediaWave Sdn Bhd', 'talent@mediawave.my', '5f4dcc3b5aa765d61d8327deb882cf99', 'company', '03-9012-3456', NULL, NULL, 'A digital media and entertainment company producing original content, managing social media platforms, and running a popular online news portal.', 'https://www.mediawave.my', NULL, '2026-06-23 06:46:20', 'approved', NULL, 'Media & Entertainment', NULL),
(11, 'AgroFresh Sdn Bhd', 'careers@agrofresh.com.my', '5f4dcc3b5aa765d61d8327deb882cf99', 'company', '03-0123-4567', NULL, NULL, 'A modern agritech startup leveraging IoT, data analytics, and sustainable farming techniques to revolutionise food production in Malaysia.', 'https://www.agrofresh.com.my', NULL, '2026-06-23 06:46:20', 'approved', NULL, 'Agriculture & Agritech', NULL),
(12, 'Ahmad Faris bin Zulkifli', 'ahmad.faris@student.utm.my', '5f4dcc3b5aa765d61d8327deb882cf99', 'student', '011-1234-5678', 'A motivated final-year Computer Science student passionate about web development and machine learning.', NULL, NULL, NULL, NULL, '2026-06-23 06:46:20', 'approved', 'Bachelor of Computer Science (Hons), Universiti Teknologi Malaysia. CGPA: 3.72. Expected graduation: June 2025.', NULL, 'PHP, Python, JavaScript, MySQL, Laravel, Bootstrap, Git'),
(13, 'Nurul Aina binti Hamid', 'nurul.aina@student.um.edu.my', '5f4dcc3b5aa765d61d8327deb882cf99', 'student', '011-2345-6789', 'Aspiring finance professional with a keen interest in investment analysis and corporate valuation.', NULL, NULL, NULL, NULL, '2026-06-23 06:46:20', 'approved', 'Bachelor of Business Administration (Finance), Universiti Malaya. CGPA: 3.85. Dean\'s List 2023/2024.', NULL, 'Financial Analysis, MS Excel, Power BI, Bloomberg Terminal, Financial Modelling'),
(14, 'Rajesh Kumar a/l Subramaniam', 'rajesh.kumar@student.upm.edu.my', '5f4dcc3b5aa765d61d8327deb882cf99', 'student', '011-3456-7890', 'Enthusiastic data science student who loves building predictive models and working with large datasets.', NULL, NULL, NULL, 'uploads/resumes/resume_6a3aa7754e06f_1782228853.pdf', '2026-06-23 06:46:20', 'approved', 'Bachelor of Science (Data Science), Universiti Putra Malaysia. CGPA: 3.61. Modules: Machine Learning, Big Data Analytics, Data Visualisation.', NULL, 'Python, R, SQL, Tableau, TensorFlow, Pandas, NumPy'),
(15, 'Siti Hajar binti Mohd Nor', 'siti.hajar@student.uitm.edu.my', '5f4dcc3b5aa765d61d8327deb882cf99', 'student', '011-4567-8901', 'Creative graphic design student with a passion for branding, UI/UX, and visual storytelling.', NULL, NULL, NULL, NULL, '2026-06-23 06:46:20', 'approved', 'Bachelor of Art & Design (Graphic Design), Universiti Teknologi MARA. CGPA: 3.78. Portfolio Award Winner 2024.', NULL, 'Adobe Photoshop, Illustrator, Figma, After Effects, UI/UX Design, Canva'),
(16, 'Lim Wei Jian', 'lim.weijian@student.mmu.edu.my', '5f4dcc3b5aa765d61d8327deb882cf99', 'student', '011-5678-9012', 'Software engineering student focused on mobile app development and cloud-based systems.', NULL, NULL, NULL, NULL, '2026-06-23 06:46:20', 'approved', 'Bachelor of Software Engineering (Hons), Multimedia University. CGPA: 3.55. Completed Android & iOS development courses.', NULL, 'Java, Kotlin, Swift, Flutter, Firebase, REST APIs, Android Studio'),
(17, 'Amirah Syazwani binti Azman', 'amirah.syazwani@student.usm.my', '5f4dcc3b5aa765d61d8327deb882cf99', 'student', '011-6789-0123', 'Marketing student with hands-on experience in social media management and content creation.', NULL, NULL, NULL, NULL, '2026-06-23 06:46:20', 'approved', 'Bachelor of Mass Communication (Marketing), Universiti Sains Malaysia. CGPA: 3.68. Internship experience with a local SME.', NULL, 'Social Media Marketing, Content Writing, SEO, Google Analytics, Copywriting, Canva'),
(18, 'Darren Ong Zi Yang', 'darren.ong@student.taylors.edu.my', '5f4dcc3b5aa765d61d8327deb882cf99', 'student', '011-7890-1234', 'Civil engineering student with strong interest in green infrastructure and sustainable construction.', NULL, NULL, NULL, NULL, '2026-06-23 06:46:20', 'approved', 'Bachelor of Civil Engineering (Hons), Taylor\'s University. CGPA: 3.44. Active member of Engineering Society.', NULL, 'AutoCAD, Civil 3D, STAAD Pro, MS Project, Structural Analysis, BIM'),
(19, 'Kavitha a/p Krishnan', 'kavitha.krishnan@student.sunway.edu.my', '5f4dcc3b5aa765d61d8327deb882cf99', 'student', '011-8901-2345', 'Accounting student passionate about auditing, taxation, and corporate governance.', NULL, NULL, NULL, NULL, '2026-06-23 06:46:20', 'approved', 'Bachelor of Accounting (Hons), Sunway University. CGPA: 3.91. ACCA part-qualified.', NULL, 'Accounting, Auditing, Taxation, MS Excel, MYOB, SAP, Financial Reporting'),
(20, 'Muhammad Haziq bin Roslan', 'haziq.roslan@student.iium.edu.my', '5f4dcc3b5aa765d61d8327deb882cf99', 'student', '011-9012-3456', 'IT student specialising in cybersecurity with hands-on experience in penetration testing and network security.', NULL, NULL, NULL, NULL, '2026-06-23 06:46:20', 'approved', 'Bachelor of Information Technology (Information Security), IIUM. CGPA: 3.59.', NULL, 'Network Security, Ethical Hacking, Kali Linux, Wireshark, Python, Cybersecurity'),
(21, 'Chong Mei Ling', 'chong.meiling@student.newera.edu.my', '5f4dcc3b5aa765d61d8327deb882cf99', 'student', '011-0123-4567', 'Logistics and supply chain student with a passion for operations management and e-commerce fulfilment.', NULL, NULL, NULL, NULL, '2026-06-23 06:46:20', 'approved', 'Bachelor of Business (Logistics & Supply Chain Management), New Era University College. CGPA: 3.38.', NULL, 'Supply Chain Management, Inventory Management, MS Excel, SAP, Logistics Planning');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `applications`
--
ALTER TABLE `applications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `student_id` (`student_id`),
  ADD KEY `job_id` (`job_id`);

--
-- Indexes for table `jobs`
--
ALTER TABLE `jobs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `company_id` (`company_id`);

--
-- Indexes for table `password_resets`
--
ALTER TABLE `password_resets`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `reminders`
--
ALTER TABLE `reminders`
  ADD PRIMARY KEY (`id`),
  ADD KEY `student_id` (`student_id`),
  ADD KEY `app_id` (`app_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `applications`
--
ALTER TABLE `applications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `jobs`
--
ALTER TABLE `jobs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=24;

--
-- AUTO_INCREMENT for table `password_resets`
--
ALTER TABLE `password_resets`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `reminders`
--
ALTER TABLE `reminders`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=25;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `applications`
--
ALTER TABLE `applications`
  ADD CONSTRAINT `applications_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `applications_ibfk_2` FOREIGN KEY (`job_id`) REFERENCES `jobs` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `jobs`
--
ALTER TABLE `jobs`
  ADD CONSTRAINT `jobs_ibfk_1` FOREIGN KEY (`company_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `reminders`
--
ALTER TABLE `reminders`
  ADD CONSTRAINT `reminders_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `reminders_ibfk_2` FOREIGN KEY (`app_id`) REFERENCES `applications` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
