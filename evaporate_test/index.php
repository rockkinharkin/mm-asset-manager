<!DOCTYPE html>
<html>
<head>
   <title>Evaporate Example</title>
   <script src="https://sdk.amazonaws.com/js/aws-sdk-2.2.43.min.js"></script>
   <script language="javascript" type="text/javascript" src="./evaporate.js"></script>
</head>
<body>
  <h1>Using EvaporateJS with AWS V4 Signatures</h1>
  <div>
    <h2>Demo</h2>
    <input type="file" id="files"  multiple />
  </div>
  <p>To get this code working, you'll need to setup your S3 bucket first (see the <a href="https://github.com/TTLabs/EvaporateJS#configuring-the-aws-s3-bucket">README</a>). You'll also need to change <strong>this</strong> file's <code>Evaporate.create()</code> call:</p>
  <ul>
    <li>aws_key -- change this to your AWS_ACCESS_KEY_ID</li>
    <li>awsRegion -- change if your bucket is not us-east-1</li>
    <li>bucket -- set this to your actual bucket name</li>
  </ul>


  <script language="javascript">

  Evaporate.create({
    /* START EDITS */
    aws_key: 'AKIAIIOIEKTS47A7MR3A', // REQUIRED -- set this to your AWS_ACCESS_KEY_ID
    bucket: 'courses.makematic.com', // REQUIRED -- set this to your s3 bucket name
    awsRegion: 'eu-west-1', // OPTIONAL -- change this if your bucket is outside us-east-1
    /* END EDITS */
    signerUrl: './s3_sign.php',
    awsSignatureVersion: '4',
    computeContentMd5: true,
    cryptoMd5Method: function (data) { return AWS.util.crypto.md5(data, 'base64'); },
    cryptoHexEncodedHash256: function (data) { return AWS.util.crypto.sha256(data, 'hex'); }
  })
  .then(
    // Successfully created evaporate instance `_e_`
    function success(_e_) {
      var fileInput = document.getElementById('files'),
          filePromises = [];

      // Start a new evaporate upload anytime new files are added in the file input
      fileInput.onchange = function(evt) {
        var files = evt.target.files;
        for (var i = 0; i < files.length; i++) {
          var promise = _e_.add({
            name: '01_creative-learning/'+files[i].name+ Math.floor(1000000000*Math.random()), // just changed this last night.
            file: files[i],
            progress: function (progress) {
              console.log('making progress: ' + progress);
            }
          })
          .then(function (awsKey) {
            console.log(awsKey, 'complete!');
          });
          filePromises.push(promise);
        }

        // Wait until all promises are complete
        Promise.all(filePromises)
          .then(function () {
            console.log('All files were uploaded successfully.');
          }, function (reason) {
            console.log('All files were not uploaded successfully:', reason);
          });

        // Clear out the file picker input
        evt.target.value = '';
      };
    },

    // Failed to create new instance of evaporate
    function failure(reason) {
       console.log('Evaporate failed to initialize: ', reason)
    }
  );
  </script>
</body>
</html>
