const express = require('express');
const exphbs = require('express-handlebars');
const path = require('path');
const VietQR = require('./vietQR');

const app = express();
const PORT = process.env.PORT || 5555;
app.engine('hbs', exphbs.engine({
  extname: '.hbs',
  defaultLayout: false
}));
app.set('view engine', 'hbs');
app.set('views', './views');
app.use(express.json());
app.use(express.urlencoded({ extended: true }));
app.use(express.static('public'));
app.get('/', (req, res) => {
  res.render('index');
});
app.post('/generate-qr', (req, res) => {
  try {
    const { 
      acquierID = '970422', 
      consumerID = '100000001', 
      amount = '100000',
      additionalData = 'NIV9x86RLn'
    } = req.body;

    const vietQR = new VietQR();
    vietQR
      .setBeneficiaryOrganization(acquierID, consumerID)
      .setTransactionAmount(amount)
      .setAdditionalDataFieldTemplate(additionalData);

    const qrData = vietQR.build();
    
    res.json({ 
      success: true, 
      qrData: qrData,
      message: 'QR code generated successfully'
    });
  } catch (error) {
    res.status(500).json({ 
      success: false, 
      error: error.message 
    });
  }
});

app.listen(PORT, () => {
  console.log(`Server is running on http://localhost:${PORT}`);
});
