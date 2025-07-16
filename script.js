const city = "Auburn";
const searchinp = document.querySelector(".search input");
const searchbtn = document.querySelector(".search button");
const weatherImg = document.querySelector(".clouds");

// Function to save weather data to local storage
function saveWeatherToLocalStorage(city, data) {
  localStorage.setItem(city, JSON.stringify(data));
}

// Function to load weather data from local storage
function getWeatherFromLocalStorage(city) {
  const storedData = localStorage.getItem(city);
  return storedData ? JSON.parse(storedData) : null;
}

// Function to fetch weather data
async function weathercheck(city) {
  try {
    console.log(`Fetching weather data for ${city}...`);

    // If offline, load cached weather data
    if (!navigator.onLine) {
      const cachedData = getWeatherFromLocalStorage(city);
      if (cachedData) {
        console.log("Loaded data from local storage:", cachedData);
        displayWeather(cachedData);
        return;
      } else {
        alert("No internet connection and no cached data available.");
        return;
      }
    }

    const response = await fetch(
      `https://aarnav2-weather.infinityfreeapp.com/connection.php?city=${city}`
    );

    const text = await response.text();
    console.log("Raw Response:", text);

    if (!response.ok) {
      throw new Error(`HTTP error! Status: ${response.status}`);
    }

    let data;
    try {
      data = JSON.parse(text);
    } catch (jsonError) {
      console.error("Invalid JSON response:", text);
      alert("Server returned invalid data. Please try again later.");
      return;
    }

    if (data.error) {
      alert(data.error);
      return;
    }

    // Save to Local Storage
    saveWeatherToLocalStorage(city, data);

    // Display Data
    displayWeather(data);
  } catch (error) {
    console.error("Error fetching weather data:", error);
    alert(
      "Failed to fetch weather data. Please check your internet connection or try again later."
    );
  }
}

// Function to update UI with fetched weather data
function displayWeather(data) {
  document.querySelector(".city").innerHTML = data.city;
  document.querySelector(".temp").innerHTML = Math.round(data.temp_data) + "°C";
  document.querySelector("#humidity").innerHTML = data.Humidity + "%";
  document.querySelector("#pressure").innerHTML = data.pressure + " hPa";
  document.querySelector("#wind_speed").innerHTML = data.windspeed + " Km/H";
  weatherImg.src = data.icon || "https://openweathermap.org/img/wn/02d@2x.png"; // Default image if missing
}

// Function to display current date
function displayCurrentDate() {
  const date = new Date();
  const options = { year: "numeric", month: "long", day: "numeric" };
  const formattedDate = date.toLocaleDateString("en-US", options);
  const currentDay = date.toLocaleString("en-US", { weekday: "long" });

  document.querySelector(
    "#currentDate"
  ).innerHTML = `${currentDay}, ${formattedDate}`;
}

// Check if offline and update UI accordingly
if (!navigator.onLine) {
  alert("No Internet connection.");
  document.querySelector(".hiden").style.display = "none";
  document.querySelector(".search").innerHTML = "No Internet Connection";
}

// Fetch default city weather on page load
weathercheck(city);

searchbtn.addEventListener("click", () => {
  const inputCity = searchinp.value.trim();
  if (inputCity) {
    weathercheck(inputCity);
  } else {
    alert("Please enter a city name.");
    weathercheck(city);
  }
});

displayCurrentDate();
