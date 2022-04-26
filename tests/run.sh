pwd
cd backend
echo "TESTING BACKEND"
php index.php
retVal=$?
echo $retVal
if [ $retVal -ne 0 ]; then
  echo "Backend Failed"
  exit $retVal
fi
cd ../frontend
echo "TESTING FRONTEND"
php index.php
retVal=$?
echo $retVal
if [ $retVal -ne 0 ]; then
  echo "Frontend Failed"
  exit $retVal
fi
