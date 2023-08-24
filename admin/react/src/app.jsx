import { useEffect, useState } from 'react'
import Table from './components/Table'

const schemas = [
  { title: 'An Organization', name: 'organizations_schema-v1.0.0' },
  { title: 'A Person', name: 'people_schema-v0.1.0' },
  { title: 'An Offer or Want', name: 'offers_wants_schema-v0.1.0' }
]

const formDefaults = {
  map_name: '',
  tag_slug: '',
  data_url: '',
  schema: 'organizations_schema-v1.0.0',
  name: '',
  lat: '',
  lon: '',
  range: '',
  locality: '',
  region: '',
  tags: '',
  tags_filter: 'or',
  tags_exact: false,
  primary_url: ''
}

export default function App() {
  // eslint-disable-next-line no-undef
  const wordpressUrl = murmurations_aggregator.wordpress_url
  const apiUrl = `${wordpressUrl}/wp-json/murmurations-aggregator/v1`

  const [formData, setFormData] = useState(formDefaults)
  const [countries, setCountries] = useState([])
  const [selectedCountry, setSelectedCountry] = useState([])
  const [profileList, setProfileList] = useState([])
  const [selectedIds, setSelectedIds] = useState([])
  const [maps, setMaps] = useState([])

  useEffect(() => {
    getCountries().then(countries => {
      const countryKeys = Object.keys(countries)
      setCountries(countryKeys)
    })
    getMaps().then(() => {
      console.log('maps fetched')
    })
  }, [])

  const getCountries = async () => {
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

  const getMaps = async () => {
    try {
      const response = await fetch(`${apiUrl}/map`)
      const responseData = await response.json()
      if (!response.ok) {
        if (response.status === 404) {
          setMaps([])
          return
        }

        alert(`Error fetching map with response: ${JSON.stringify(response)}`)
        return
      }

      setMaps(responseData)
    } catch (error) {
      alert(
        `Error getting maps, please contact the administrator, error: ${error}`
      )
    }
  }

  // Table
  const toggleSelectAll = () => {
    if (selectedIds.length === profileList.length) {
      setSelectedIds([])
    } else {
      setSelectedIds(profileList.map(response => response.id))
    }
  }

  const toggleSelect = id => {
    if (selectedIds.includes(id)) {
      setSelectedIds(selectedIds.filter(selectedId => selectedId !== id))
    } else {
      setSelectedIds([...selectedIds, id])
    }
  }

  const handleInputChange = event => {
    const { name, value, type, checked } = event.target
    const newValue = type === 'checkbox' ? checked : value

    setFormData(prevData => {
      const newData = Object.assign({}, prevData)
      newData[name] = newValue
      return newData
    })
  }

  const handleCountryChange = event => {
    const selected = Array.from(event.target.options)
      .filter(option => option.selected)
      .map(option => option.value)

    setSelectedCountry(selected)
  }

  const handleSubmit = async event => {
    event.preventDefault()

    const queryParams = []
    for (const key in formData) {
      if (
        formData[key] !== '' &&
        key !== 'data_url' &&
        key !== 'map_name' &&
        key !== 'tag_slug'
      ) {
        queryParams.push(
          `${encodeURIComponent(key)}=${encodeURIComponent(formData[key])}`
        )
      }
    }
    const queryString = queryParams.join('&')
    const pageQueries = 'page=1&page_size=500'

    const urlWithParams =
      formData.data_url +
      '?' +
      (queryString ? `${queryString}&` : '') +
      pageQueries

    try {
      const response = await fetch(urlWithParams)
      if (response.ok) {
        const responseData = await response.json()
        if (responseData.data && responseData.data.length === 0) {
          alert('Error: No data found. Please try again.')
          return
        }
        if (
          responseData.meta &&
          responseData.meta.total_pages &&
          responseData.meta.total_pages > 2
        ) {
          alert('Error: Too many pages of data. Please narrow your search.')
          return
        }
        // we have a valid response, we can save the map to the server
        const mapData = {
          name: formData.map_name,
          tag_slug: formData.tag_slug,
          index_url: formData.data_url,
          query_url: urlWithParams.replace(formData.data_url, '')
        }

        const mapResponse = await fetchRequest(`${apiUrl}/map`, 'POST', mapData)
        if (!mapResponse.ok) {
          const mapResponseData = await mapResponse.json()
          alert(
            `Map Error: ${mapResponse.status} ${JSON.stringify(
              mapResponseData
            )}`
          )
          return
        }

        // todo: save data to WordPress DB - nodes

        const dataWithIds = responseData.data.map((item, index) => {
          item.id = index + 1
          return item
        })
        setProfileList(dataWithIds)
      } else {
        alert(`Error: ${response.status} ${response}`)
      }
    } catch (error) {
      alert(`Handle Submit error: ${error}`)
    }
  }

  const handleProfilesSubmit = async event => {
    event.preventDefault()

    const selectedProfiles = profileList.filter(profile =>
      selectedIds.includes(profile.id)
    )

    for (const profile of selectedProfiles) {
      const profileData = {
        tag_slug: formData.tag_slug,
        name: profile.name
      }

      const profileResponse = await fetchRequest(
        `${apiUrl}/wp_node`,
        'POST',
        profileData
      )
      // todo: needs to summarize errors and display them in once
      if (!profileResponse.ok) {
        const profileResponseData = await profileResponse.json()
        alert(
          `Profile Error: ${profileResponse.status} ${JSON.stringify(
            profileResponseData
          )}`
        )
      }
    }

    // set everything back to default
    setFormData(formDefaults)
    setSelectedCountry([])
    setProfileList([])
    setSelectedIds([])

    // refresh maps
    await getMaps()
  }

  const fetchRequest = async (url, method, body) => {
    try {
      return await fetch(url, {
        method: method,
        headers: {
          'Content-Type': 'application/json'
        },
        body: JSON.stringify(body)
      })
    } catch (error) {
      alert(`Fetch Request error: ${error}`)
    }
  }

  return (
    <div>
      <h1 className="text-3xl">Murmurations Aggregator</h1>

      <div className="flex">
        <div className="w-1/2 mt-4">
          {profileList.length === 0 ? (
            <div>
              <h2 className="text-xl">Create Data Source</h2>
              <form onSubmit={handleSubmit} className="p-6">
                <div className="mb-4">
                  <label
                    className="block text-gray-700 font-bold mb-2"
                    htmlFor="map_name"
                  >
                    Map Name
                  </label>
                  <input
                    type="text"
                    id="map_name"
                    name="map_name"
                    value={formData.map_name}
                    onChange={handleInputChange}
                    className="w-full border rounded py-2 px-3"
                    required={true}
                  />
                </div>
                <div className="mb-4">
                  <label
                    className="block text-gray-700 font-bold mb-2"
                    htmlFor="tag_slug"
                  >
                    Tag Slug (The tag will be applied to all nodes)
                  </label>
                  <input
                    type="text"
                    id="tag_slug"
                    name="tag_slug"
                    value={formData.tag_slug}
                    onChange={handleInputChange}
                    className="w-full border rounded py-2 px-3"
                    required={true}
                  />
                </div>
                <div className="mb-4">
                  <label
                    className="block text-gray-700 font-bold mb-2"
                    htmlFor="data_url"
                  >
                    Data URL
                  </label>
                  <input
                    type="text"
                    id="data_url"
                    name="data_url"
                    value={formData.data_url}
                    onChange={handleInputChange}
                    className="w-full border rounded py-2 px-3"
                    required={true}
                  />
                </div>
                <div className="mb-4">
                  <label
                    className="block text-gray-700 font-bold mb-2"
                    htmlFor="schema"
                  >
                    Schema
                  </label>
                  <select
                    id="schema"
                    name="schema"
                    value={formData.schema}
                    onChange={handleInputChange}
                    className="w-full border rounded py-2 px-3"
                  >
                    {schemas.map(schema => (
                      <option key={schema.name} value={schema.name}>
                        {schema.title}
                      </option>
                    ))}
                  </select>
                </div>
                <div className="mb-4">
                  <label
                    className="block text-gray-700 font-bold mb-2"
                    htmlFor="name"
                  >
                    Name
                  </label>
                  <input
                    type="text"
                    id="name"
                    name="name"
                    value={formData.name}
                    onChange={handleInputChange}
                    className="w-full border rounded py-2 px-3"
                  />
                </div>
                <div className="mb-4">
                  <label
                    className="block text-gray-700 font-bold mb-2"
                    htmlFor="lat"
                  >
                    Latitude
                  </label>
                  <input
                    type="number"
                    id="lat"
                    name="lat"
                    min="-90"
                    max="90"
                    step="any"
                    value={formData.lat}
                    onChange={handleInputChange}
                    className="w-full border rounded py-2 px-3"
                  />
                </div>
                <div className="mb-4">
                  <label
                    className="block text-gray-700 font-bold mb-2"
                    htmlFor="lon"
                  >
                    Longitude
                  </label>
                  <input
                    type="number"
                    id="lon"
                    name="lon"
                    min="-180"
                    max="180"
                    step="any"
                    value={formData.lon}
                    onChange={handleInputChange}
                    className="w-full border rounded py-2 px-3"
                  />
                </div>
                <div className="mb-4">
                  <label
                    className="block text-gray-700 font-bold mb-2"
                    htmlFor="range"
                  >
                    Range (i.e. 25km, 15mi)
                  </label>
                  <input
                    type="text"
                    id="range"
                    name="range"
                    value={formData.range}
                    onChange={handleInputChange}
                    className="w-full border rounded py-2 px-3"
                  />
                </div>
                <div className="mb-4">
                  <label
                    className="block text-gray-700 font-bold mb-2"
                    htmlFor="locality"
                  >
                    Locality
                  </label>
                  <input
                    type="text"
                    id="locality"
                    name="locality"
                    value={formData.locality}
                    onChange={handleInputChange}
                    className="w-full border rounded py-2 px-3"
                  />
                </div>
                <div className="mb-4">
                  <label
                    className="block text-gray-700 font-bold mb-2"
                    htmlFor="region"
                  >
                    Region
                  </label>
                  <input
                    type="text"
                    id="region"
                    name="region"
                    value={formData.region}
                    onChange={handleInputChange}
                    className="w-full border rounded py-2 px-3"
                  />
                </div>
                <div className="mb-4">
                  <label
                    className="block text-gray-700 font-bold mb-2"
                    htmlFor="country"
                  >
                    Country
                  </label>
                  <select
                    multiple={true}
                    id="country"
                    name="country"
                    value={selectedCountry}
                    onChange={handleCountryChange}
                    className="w-full border rounded py-2 px-3"
                  >
                    {countries.map(country => (
                      <option
                        key={country}
                        value={country}
                        selected={selectedCountry.includes(country)}
                      >
                        {country}
                      </option>
                    ))}
                  </select>
                </div>
                <div className="mb-4">
                  <label
                    className="block text-gray-700 font-bold mb-2"
                    htmlFor="tags"
                  >
                    Tags
                  </label>
                  <input
                    type="text"
                    id="tags"
                    name="tags"
                    value={formData.tags}
                    onChange={handleInputChange}
                    className="w-full border rounded py-2 px-3"
                  />
                </div>
                <div className="mb-4">
                  <label
                    className="block text-gray-700 font-bold mb-2"
                    htmlFor="tags_filter"
                  >
                    Tags Filter
                  </label>
                  <select
                    id="tags_filter"
                    name="tags_filter"
                    value={formData.tags_filter}
                    onChange={handleInputChange}
                    className="w-full border rounded py-2 px-3"
                  >
                    <option value="or">OR</option>
                    <option value="and">AND</option>
                  </select>
                </div>
                <div className="mb-4">
                  <label
                    className="block text-gray-700 font-bold mb-2"
                    htmlFor="tags_exact"
                  >
                    Tags Exact
                  </label>
                  <input
                    type="checkbox"
                    id="tags_exact"
                    name="tags_exact"
                    checked={formData.tags_exact}
                    onChange={handleInputChange}
                    className="mr-2"
                  />
                </div>
                <div className="mb-4">
                  <label
                    className="block text-gray-700 font-bold mb-2"
                    htmlFor="primary_url"
                  >
                    Primary URL
                  </label>
                  <input
                    type="text"
                    id="primary_url"
                    name="primary_url"
                    value={formData.primary_url}
                    onChange={handleInputChange}
                    className="w-full border rounded py-2 px-3"
                  />
                </div>
                <div className="mt-6">
                  <button
                    type="submit"
                    className="rounded-full bg-orange-500 px-4 py-2 font-bold text-white text-lg active:scale-90 hover:scale-110 hover:bg-orange-400 disabled:opacity-75"
                  >
                    Submit
                  </button>
                </div>
              </form>
            </div>
          ) : (
            <div>
              <h2 className="text-xl mt-4">Data Select</h2>
              <Table
                tableList={profileList}
                selectedIds={selectedIds}
                onSelectAll={toggleSelectAll}
                onSelect={toggleSelect}
              />
              <form onSubmit={handleProfilesSubmit} className="p-6">
                <div className="mt-6">
                  <button
                    type="submit"
                    className="rounded-full bg-orange-500 px-4 py-2 font-bold text-white text-lg active:scale-90 hover:scale-110 hover:bg-orange-400 disabled:opacity-75"
                  >
                    Submit
                  </button>
                </div>
              </form>
            </div>
          )}
        </div>
        <div className="w-1/2 mt-4">
          <h2 className="text-xl">Map Data</h2>
          {maps.length > 0 ? (
            maps.map((map, index) => (
              <div className="bg-white p-4 rounded shadow-md mt-4" key={index}>
                <h2 className="text-xl font-semibold mb-2">{map.name}</h2>
                <p>
                  <strong>Index URL:</strong> {map.index_url}
                </p>
                <p>
                  <strong>Query URL:</strong> {map.query_url}
                </p>
                <p>
                  <strong>Tag Slug:</strong> {map.tag_slug}
                </p>
                <p>
                  <strong>Map Center:</strong>{' '}
                  {map.map_center_lon + ',' + map.map_center_lat}
                </p>
                <p>
                  <strong>Map Scale:</strong> {map.map_scale}
                </p>
                <p>
                  <strong>Created At:</strong> {map.created_at}
                </p>
                <p>
                  <strong>Updated At:</strong> {map.updated_at}
                </p>
              </div>
            ))
          ) : (
            <p>No maps found.</p>
          )}
        </div>
      </div>
    </div>
  )
}
