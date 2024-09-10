export const getSchemas = async (env) => {
  let url = 'https://test-library.murmurations.network/v2/schemas'
  if (env === 'production') {
    url = 'https://library.murmurations.network/v2/schemas'
  }
  try {
    const response = await fetch(url)
    const data = await response.json()

    // Remove test-schema from the list
    return data?.data
      ?.map(schema => ({
        title: schema.title,
        name: schema.name
      }))
      .filter(schema => !schema.name.includes('test_schema'))
      .sort((a, b) => a.title.localeCompare(b.title))
  } catch (error) {
    alert(
      `Error getting schemas, please contact the administrator, error: ${error}`
    )
  }
}
