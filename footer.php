<?php
/**
 * Mero Sahayata - Global Footer Component
 */
?>
<footer class="site-footer">
    <div class="footer-container">
        <div class="footer-col">
            <h4 data-np="मेरो सहायता प्रणाली" data-en="Mero Sahayata System">मेरो सहायता प्रणाली</h4>
            <p style="line-height:1.7;margin-bottom:12px;" data-np="नेपालका नागरिकहरूलाई सरकारी सेवाहरूको जटिल नियम, आवश्यक कागजात, र प्रक्रियागत अन्योलता हटाउन विकास गरिएको नियम-आधारित निर्णय सहायता प्रणाली।" data-en="A rule-based public service guidance and decision support system built to assist low digital-literate citizens in navigating administrative procedures in Nepal.">
                नेपालका नागरिकहरूलाई सरकारी सेवाहरूको जटिल नियम, आवश्यक कागजात, र प्रक्रियागत अन्योलता हटाउन विकास गरिएको नियम-आधारित निर्णय सहायता प्रणाली।
            </p>
            <div style="font-size:0.85rem;color:#cbd5e1;">
                <strong>Academic Scope:</strong> Bachelor in Computer Application (BCA) 6th Semester Project
            </div>
        </div>

        <div class="footer-col">
            <h4 data-np="मुख्य सेवाहरू" data-en="Primary Services">मुख्य सेवाहरू</h4>
            <ul>
                <li><a href="javascript:void(0)" onclick="openServiceWizard(3)">ई-पासपोर्ट (E-Passport)</a></li>
                <li><a href="javascript:void(0)" onclick="openServiceWizard(2)">राष्ट्रिय परिचयपत्र (National ID)</a></li>
                <li><a href="javascript:void(0)" onclick="openServiceWizard(1)">नागरिकता (Citizenship)</a></li>
                <li><a href="javascript:void(0)" onclick="openServiceWizard(4)">सवारी चालक अनुमति पत्र (License)</a></li>
            </ul>
        </div>

        <div class="footer-col">
            <h4 data-np="महत्त्वपूर्ण सरकारी सम्पर्क" data-en="Govt Hotlines">महत्त्वपूर्ण सरकारी सम्पर्क</h4>
            <ul>
                <li><strong>हेलो सरकार:</strong> ११११ (Toll-Free)</li>
                <li><strong>राहदानी विभाग:</strong> ०१-५९७०३६०</li>
                <li><strong>राष्ट्रिय परिचयपत्र विभाग:</strong> ०१-४२००८९४</li>
                <li><strong>ट्राफिक प्रहरी:</strong> १०३</li>
            </ul>
        </div>
    </div>

    <div class="footer-bottom">
        <p>&copy; <?= date('Y') ?> मेरो सहायता (Mero Sahayata) - Rule-Based Public Service Guidance System. All Rights Reserved.</p>
    </div>
</footer>

<!-- JavaScript Engine Scripts -->
<script src="assets/js/speech.js"></script>
<script src="assets/js/app.js"></script>
</body>
</html>
