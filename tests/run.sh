pwd
cd backend
php index.php
retVal=$?
echo $retVal
if [ $retVal -ne 0 ]; then
  echo "Backend Failed"
  exit $retVal
fi
cd ../frontend
php index.php
retVal=$?
echo $retVal
if [ $retVal -ne 0 ]; then
  echo "Frontend Failed"
  exit $retVal
fi
