import { useEffect, useState } from 'react'
import Table from './components/Table'
import MapSettings from './components/MapSettings'
import { createId } from '@paralleldrive/cuid2'

const schemas = [
  { title: 'An Organization', name: 'organizations_schema-v1.0.0' },
  { title: 'A Person', name: 'people_schema-v0.1.0' },
  { title: 'An Offer or Want', name: 'offers_wants_schema-v0.1.0' }
]

const formDefaults = {
  map_name: '',
  map_center_lat: '',
  map_center_lon: '',
  map_scale: '',
  data_url: '',
  schema: 'organizations_schema-v1.0.0',
  name: '',
  lat: '',
  lon: '',
  range: '',
  locality: '',
  region: '',
  country: '',
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
  const [isLoading, setIsLoading] = useState(false)
  const [progress, setProgress] = useState(0)
  const [isEdit, setIsEdit] = useState(false)
  const [selectedStatusOption, setSelectedStatusOption] = useState('publish')
  const [isRetrieving, setIsRetrieving] = useState(false)

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
      const response = await fetch(`${apiUrl}/maps`)
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
    setIsLoading(true)
    setIsRetrieving(false)

    const queryParams = []
    for (const key in formData) {
      if (formData[key] !== '' && key !== 'data_url' && key !== 'map_name') {
        queryParams.push(
          `${encodeURIComponent(key)}=${encodeURIComponent(formData[key])}`
        )
      }
    }

    // handle country array
    if (selectedCountry.length > 0) {
      queryParams.push(
        `${encodeURIComponent('country')}=${encodeURIComponent(
          selectedCountry.join(',')
        )}`
      )
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
        const tagSlug = 'murm_' + createId()
        const mapData = {
          name: formData.map_name,
          tag_slug: tagSlug,
          index_url: formData.data_url,
          query_url: urlWithParams.replace(formData.data_url, '')
        }

        const mapResponse = await fetchRequest(
          `${apiUrl}/maps`,
          'POST',
          mapData
        )
        const mapResponseData = await mapResponse.json()
        if (!mapResponse.ok) {
          alert(
            `Map Error: ${mapResponse.status} ${JSON.stringify(
              mapResponseData
            )}`
          )
          return
        }

        // set the profileList and save the data to wpdb
        const profiles = responseData.data
        const dataWithIds = []
        const progressStep = 100 / profiles.length
        for (let i = 0; i < profiles.length; i++) {
          // update progress
          if ((i + 1) * progressStep > 100) {
            setProgress(100)
          } else {
            setProgress((i + 1) * progressStep)
          }

          const profile = profiles[i]
          let profile_data = ''
          if (profile.profile_url) {
            const response = await fetch(profile.profile_url)
            if (response.ok) {
              profile_data = await response.json()
            }
          }
          profile.id = i + 1
          profile.profile_data = profile_data
          profile.status = 'new'
          profile.map_id = mapResponseData.map_id
          profile.tag_slug = tagSlug

          // save data to wpdb
          // todo: status needs to update according to the settings
          const profileData = {
            profile_url: profile.profile_url,
            data: profile.profile_data,
            map_id: profile.map_id,
            status: profile.status
          }

          const profileResponse = await fetchRequest(
            `${apiUrl}/nodes`,
            'POST',
            profileData
          )

          if (!profileResponse.ok) {
            const profileResponseData = await profileResponse.json()
            alert(
              `Unable to save profiles to wpdb, errors: ${JSON.stringify(
                profileResponseData
              )}. Please delete the map and try again.`
            )
            return
          }

          // set extra nodes
          profile.extra_notes = profile_data === '' ? 'unavailable' : ''

          dataWithIds.push(profile)
        }
        setProfileList(dataWithIds)
      } else {
        alert(`Error: ${response.status} ${response}`)
      }
    } catch (error) {
      alert(
        `Handle Submit error: ${JSON.stringify(
          error
        )}, please delete the map and retry again.`
      )
    } finally {
      setIsLoading(false)
      setProgress(0)
    }
  }

  const handleProfilesSubmit = async event => {
    event.preventDefault()
    setIsLoading(true)

    try {
      // get the selected profiles
      const selectedProfiles = profileList.filter(profile =>
        selectedIds.includes(profile.id)
      )
      const progressStep = 100 / selectedProfiles.length

      for (let i = 0; i < selectedProfiles.length; i++) {
        // update progress
        if ((i + 1) * progressStep > 100) {
          setProgress(100)
        } else {
          setProgress((i + 1) * progressStep)
        }

        const profile = selectedProfiles[i]

        // if the profile wants to publish, it will create post in WordPress
        if (selectedStatusOption === 'publish') {
          const profileData = {
            tag_slug: profile.tag_slug,
            profile: profile
          }

          const profileResponse = await fetchRequest(
            `${apiUrl}/wp-nodes`,
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
        // Otherwise, dismiss, ignore status will update the status of nodes table
        else {
          const profileData = {
            map_id: profile.map_id,
            profile_url: profile.profile_url,
            status: selectedStatusOption
          }
          const profileResponse = await fetchRequest(
            `${apiUrl}/nodes-status`,
            'POST',
            profileData
          )

          if (!profileResponse.ok) {
            const profileResponseData = await profileResponse.json()
            alert(
              `Profile Error: ${profileResponse.status} ${JSON.stringify(
                profileResponseData
              )}`
            )
          }
        }
      }

      // remove selected profiles
      const newProfileList = profileList.filter(
        profile => !selectedIds.includes(profile.id)
      )

      // if the extra_notes of all profiles are unavailable, it means all nodes are handled, we can refresh the page
      if (
        newProfileList.length === 0 ||
        newProfileList.every(profile => profile.extra_notes === 'unavailable')
      ) {
        setFormData(formDefaults)
        setSelectedCountry([])
        setProfileList([])
        await getMaps()
      } else {
        setProfileList(newProfileList)
      }
    } catch (error) {
      alert(
        `Handle Profiles Submit error: ${JSON.stringify(
          error
        )}, please delete the map and retry again.`
      )
    } finally {
      // set everything back to default
      setSelectedIds([])
      setProgress(0)
      setIsLoading(false)
    }
  }

  const handleRetrieve = async (map_id, request_url, tag_slug) => {
    setIsLoading(true)
    setIsRetrieving(true)

    try {
      // get data from request_url
      const response = await fetch(request_url)
      const responseData = await response.json()
      if (!response.ok) {
        alert(
          `Retrieve Error: ${response.status} ${JSON.stringify(responseData)}`
        )
        return
      }

      // check with wpdb
      const profiles = responseData.data
      const dataWithIds = []
      let current_id = 1
      for (let profile of profiles) {
        let profile_data = ''
        if (profile.profile_url) {
          const response = await fetch(profile.profile_url)
          if (response.ok) {
            profile_data = await response.json()
          }
        }
        profile.id = current_id
        profile.profile_data = profile_data
        profile.tag_slug = tag_slug
        profile.map_id = map_id

        // compare with wpdb
        const profileData = {
          map_id: map_id,
          data: profile_data,
          profile_url: profile.profile_url
        }

        const profileResponse = await fetchRequest(
          `${apiUrl}/nodes-comparison`,
          'POST',
          profileData
        )

        if (profileResponse.status === 404) {
          profile.status = 'new'
        }

        if (profileResponse.ok) {
          const profileResponseData = await profileResponse.json()
          // if the profile is ignored, don't show up again
          if (profileResponseData.status === 'ignore') {
            continue
          }

          profile.status = profileResponseData.status
          if (profileResponseData.has_update) {
            profile.extra_notes = 'see updates'
          }
        }

        if (profile_data === '') {
          profile.extra_notes = 'unavailable'
        }

        current_id++
        dataWithIds.push(profile)
      }
      setProfileList(dataWithIds)
    } catch (error) {
      alert(`Retrieve node error: ${JSON.stringify(error)}`)
    } finally {
      setIsLoading(false)
    }
  }

  const handleRetrieveProfilesSubmit = async event => {
    event.preventDefault()
    setIsLoading(true)

    try {
      // get the selected profiles
      const selectedProfiles = profileList.filter(profile =>
        selectedIds.includes(profile.id)
      )
      const progressStep = 100 / selectedProfiles.length

      for (let i = 0; i < selectedProfiles.length; i++) {
        // update progress
        if ((i + 1) * progressStep > 100) {
          setProgress(100)
        } else {
          setProgress((i + 1) * progressStep)
        }

        const profile = selectedProfiles[i]

        // need to update the node first
        if (profile.extra_notes === 'see updates') {
          const profileData = {
            map_id: profile.map_id,
            profile_url: profile.profile_url,
            data: profile.profile_data
          }

          const profileResponse = await fetchRequest(
            `${apiUrl}/nodes`,
            'PUT',
            profileData
          )

          if (!profileResponse.ok) {
            const profileResponseData = await profileResponse.json()
            alert(
              `Update Profile Error: ${profileResponse.status} ${JSON.stringify(
                profileResponseData
              )}`
            )
          }
        }

        const profileStatus = profile.status
        // if original status and selected status are both publish, we need to update the post
        if (selectedStatusOption === 'publish' && profileStatus === 'publish') {
          const profileData = {
            profile: profile
          }

          const profileResponse = await fetchRequest(
            `${apiUrl}/wp-nodes`,
            'PUT',
            profileData
          )

          if (!profileResponse.ok) {
            const profileResponseData = await profileResponse.json()
            alert(
              `Update Profile Error: ${profileResponse.status} ${JSON.stringify(
                profileResponseData
              )}`
            )
          }
        }

        if (
          selectedStatusOption === 'publish' &&
          (profileStatus === 'new' || profileStatus === 'dismiss')
        ) {
          const profileData = {
            tag_slug: profile.tag_slug,
            profile: profile
          }

          const profileResponse = await fetchRequest(
            `${apiUrl}/wp-nodes`,
            'POST',
            profileData
          )
          if (!profileResponse.ok) {
            const profileResponseData = await profileResponse.json()
            alert(
              `Create Profile Error: ${profileResponse.status} ${JSON.stringify(
                profileResponseData
              )}`
            )
          }
        }

        if (
          (selectedStatusOption === 'dismiss' ||
            selectedStatusOption === 'ignore') &&
          profileStatus === 'publish'
        ) {
          // delete the post
          const profileData = {
            profile: profile
          }
          const profileResponse = await fetchRequest(
            `${apiUrl}/wp-nodes`,
            'DELETE',
            profileData
          )

          if (!profileResponse.ok) {
            const profileResponseData = await profileResponse.json()
            alert(
              `Delete Profile Error: ${profileResponse.status} ${JSON.stringify(
                profileResponseData
              )}`
            )
          }

          // update the status of the node
          const profileData2 = {
            map_id: profile.map_id,
            profile_url: profile.profile_url,
            status: selectedStatusOption
          }

          const profileResponse2 = await fetchRequest(
            `${apiUrl}/nodes-status`,
            'POST',
            profileData2
          )

          if (!profileResponse2.ok) {
            const profileResponseData2 = await profileResponse2.json()
            alert(
              `Node Error: ${profileResponse2.status} ${JSON.stringify(
                profileResponseData2
              )}`
            )
          }
        }

        if (selectedStatusOption === 'ignore' && profileStatus === 'dismiss') {
          // update the status of the node
          const profileData = {
            map_id: profile.map_id,
            profile_url: profile.profile_url,
            status: selectedStatusOption
          }

          const profileResponse = await fetchRequest(
            `${apiUrl}/nodes-status`,
            'POST',
            profileData
          )

          if (!profileResponse.ok) {
            const profileResponseData = await profileResponse.json()
            alert(
              `Node Error: ${profileResponse.status} ${JSON.stringify(
                profileResponseData
              )}`
            )
          }
        }
      }

      // remove selected profiles
      const newProfileList = profileList.filter(
        profile => !selectedIds.includes(profile.id)
      )

      // if the extra_notes of all profiles are unavailable, it means all nodes are handled, we can refresh the page
      if (
        newProfileList.length === 0 ||
        newProfileList.every(profile => profile.extra_notes === 'unavailable')
      ) {
        setFormData(formDefaults)
        setSelectedCountry([])
        setProfileList([])
        await getMaps()
      } else {
        setProfileList(newProfileList)
      }
    } catch (error) {
      alert(
        `Handle Profiles Submit error: ${JSON.stringify(
          error
        )}, please delete the map and retry again.`
      )
    } finally {
      // set everything back to default
      setSelectedIds([])
      setProgress(0)
      setIsLoading(false)
    }
  }

  const handleEdit = async tag_slug => {
    setIsEdit(true)
    setProfileList([])
    const map = maps.find(map => map.tag_slug === tag_slug)
    setFormData({
      map_name: map.name,
      map_center_lat: map.map_center_lat,
      map_center_lon: map.map_center_lon,
      map_scale: map.map_scale,
      tag_slug: map.tag_slug
    })
  }

  const handleEditSubmit = async event => {
    event.preventDefault()

    setIsLoading(true)
    const mapData = {
      name: formData.map_name,
      map_center_lat: formData.map_center_lat,
      map_center_lon: formData.map_center_lon,
      map_scale: formData.map_scale
    }

    try {
      const mapResponse = await fetchRequest(
        `${apiUrl}/maps/${formData.tag_slug}`,
        'PUT',
        mapData
      )
      if (!mapResponse.ok) {
        const mapResponseData = await mapResponse.json()
        alert(
          `Map Error: ${mapResponse.status} ${JSON.stringify(mapResponseData)}`
        )
      }
    } catch (error) {
      alert(`Edit map error: ${JSON.stringify(error)}`)
    } finally {
      setIsEdit(false)
      setIsLoading(false)
      setFormData(formDefaults)
      await getMaps()
    }
  }

  const handleDelete = async map_id => {
    setIsLoading(true)

    try {
      const mapResponse = await fetchRequest(
        `${apiUrl}/maps/${map_id}`,
        'DELETE'
      )
      if (!mapResponse.ok) {
        const mapResponseData = await mapResponse.json()
        alert(
          `Map Error: ${mapResponse.status} ${JSON.stringify(mapResponseData)}`
        )
      }
    } catch (error) {
      alert(`Delete map error: ${JSON.stringify(error)}`)
    } finally {
      setIsLoading(false)
      await getMaps()
    }
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

  const handleDropdownChange = function () {
    const selected = document.getElementById('status-option').value
    setSelectedStatusOption(selected)
  }

  return (
    <div>
      <h1 className="text-3xl">Murmurations Aggregator</h1>

      <div className="flex">
        <div className="w-1/2 mt-4 p-4">
          {profileList.length === 0 ? (
            isEdit ? (
              <div>
                <h2 className="text-xl">Edit Data Source</h2>
                <form onSubmit={handleEditSubmit} className="p-6">
                  <MapSettings
                    formData={formData}
                    handleInputChange={handleInputChange}
                  />
                  <div className="mt-6">
                    <button
                      type="submit"
                      className={`rounded-full bg-orange-500 px-4 py-2 font-bold text-white text-lg active:scale-90 hover:scale-110 hover:bg-orange-400 disabled:opacity-75 ${
                        isLoading ? 'opacity-50 cursor-not-allowed' : ''
                      }`}
                    >
                      {isLoading ? 'Submitting...' : 'Submit'}
                    </button>
                  </div>
                </form>
              </div>
            ) : (
              <div>
                <h2 className="text-xl">Create Data Source</h2>
                <form onSubmit={handleSubmit} className="p-6">
                  <MapSettings
                    formData={formData}
                    handleInputChange={handleInputChange}
                  />
                  <h2 className="text-xl mt-4">Data Source</h2>
                  <div className="border-2 border-dotted border-red-500 p-4 mt-2">
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
                  </div>
                  {isLoading && (
                    <div className="relative mt-6">
                      <progress
                        className="w-full bg-orange-500 h-8 mt-2 rounded"
                        value={progress}
                        max="100"
                      />
                      <div className="absolute text-white top-3.5 left-0 right-0 text-center">
                        {progress.toFixed(0)}%
                      </div>
                    </div>
                  )}
                  <div className="mt-6">
                    <button
                      type="submit"
                      className={`rounded-full bg-orange-500 px-4 py-2 font-bold text-white text-lg active:scale-90 hover:scale-110 hover:bg-orange-400 disabled:opacity-75 ${
                        isLoading ? 'opacity-50 cursor-not-allowed' : ''
                      }`}
                    >
                      {isLoading ? 'Submitting...' : 'Submit'}
                    </button>
                  </div>
                </form>
              </div>
            )
          ) : (
            <div>
              <h2 className="text-xl mt-4">Data Select</h2>
              <Table
                tableList={profileList}
                selectedIds={selectedIds}
                onSelectAll={toggleSelectAll}
                onSelect={toggleSelect}
              />
              <form
                onSubmit={
                  isRetrieving
                    ? handleRetrieveProfilesSubmit
                    : handleProfilesSubmit
                }
                className="p-6"
              >
                <div className="mt-6">
                  {isLoading && (
                    <div className="relative mt-6">
                      <progress
                        className="w-full bg-orange-500 h-8 mt-2 rounded"
                        value={progress}
                        max="100"
                      />
                      <div className="absolute text-white top-3.5 left-0 right-0 text-center">
                        {progress.toFixed(0)}%
                      </div>
                    </div>
                  )}
                  <div className="flex items-start">
                    <label className="mr-2">Select Action:</label>
                    <select
                      id="status-option"
                      value={selectedStatusOption}
                      onChange={() => handleDropdownChange()}
                      className="mr-2"
                    >
                      <option value="publish">Publish</option>
                      <option value="dismiss">Dismiss</option>
                      <option value="ignore">Ignore</option>
                    </select>
                    <button
                      type="submit"
                      className={`rounded-full bg-orange-500 px-4 py-2 font-bold text-white text-lg active:scale-90 hover:scale-110 hover:bg-orange-400 disabled:opacity-75 ${
                        isLoading ? 'opacity-50 cursor-not-allowed' : ''
                      }`}
                    >
                      {isLoading ? 'Submitting...' : 'Submit'}
                    </button>
                  </div>
                </div>
              </form>
            </div>
          )}
        </div>
        <div className="w-1/2 mt-4 p-4">
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
                <div className="box-border flex flex-wrap xl:min-w-max flex-row mt-4 justify-between">
                  <button
                    className={`my-1 mx-2 max-w-fit rounded-full bg-amber-500 px-4 py-2 font-bold text-white text-base active:scale-90 hover:scale-110 hover:bg-yellow-400 disabled:opacity-75 ${
                      isLoading ? 'opacity-50 cursor-not-allowed' : ''
                    }`}
                    onClick={() =>
                      handleRetrieve(
                        map.id,
                        map.index_url + map.query_url,
                        map.tag_slug
                      )
                    }
                  >
                    {isLoading ? 'Loading' : 'Retrieve'}
                  </button>
                  <button
                    className="my-1 mx-2 max-w-fit rounded-full bg-orange-500 px-4 py-2 font-bold text-white text-base active:scale-90 hover:scale-110 hover:bg-orange-400 disabled:opacity-75"
                    onClick={() => handleEdit(map.tag_slug)}
                  >
                    Edit
                  </button>
                  <button
                    className={`my-1 mx-2 max-w-fit rounded-full bg-red-500 px-4 py-2 font-bold text-white text-base active:scale-90 hover:scale-110 hover:bg-red-400 disabled:opacity-75 ${
                      isLoading ? 'opacity-50 cursor-not-allowed' : ''
                    }`}
                    onClick={() => handleDelete(map.id)}
                  >
                    {isLoading ? 'Loading' : 'Delete'}
                  </button>
                </div>
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
