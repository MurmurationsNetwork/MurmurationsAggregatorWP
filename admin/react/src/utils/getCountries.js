export const getCountries = async () => {
  try {
    const response = await fetch(
      'https://test-library.murmurations.network/v2/countries'
    )
    return await response.json()
  } catch (error) {
    alert(
      `Error getting countries, please contact the administrator, error: ${error}`
    )
  }
}
