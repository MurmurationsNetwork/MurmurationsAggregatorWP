import MapSettings from './MapSettings'
import { updateCustomMap } from '../utils/api'
import { formDefaults } from '../data/formDefaults'
import PropTypes from 'prop-types'

export default function EditData({
  formData,
  handleInputChange,
  setIsLoading,
  setIsEdit,
  setFormData,
  getMaps,
  isLoading
}) {
  const handleEditSubmit = async event => {
    event.preventDefault()
    setIsLoading(true)

    try {
      const response = await updateCustomMap(
        formData.map_id,
        formData.map_name,
        formData.map_center_lat,
        formData.map_center_lon,
        formData.map_scale
      )

      if (!response.ok) {
        const responseData = await response.json()
        alert(`Map Error: ${response.status} ${JSON.stringify(responseData)}`)
      }
    } catch (error) {
      alert(`Edit map error: ${error}`)
    } finally {
      setIsEdit(false)
      setIsLoading(false)
      setFormData(formDefaults)
      await getMaps()
    }
  }

  return (
    <div>
      <h2 className="text-xl">Edit Data Source</h2>
      <form onSubmit={handleEditSubmit} className="p-6">
        <MapSettings
          formData={formData}
          handleInputChange={handleInputChange}
        />
        <input type="hidden" name="map_id" value={formData.map_id} />
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
}

EditData.propTypes = {
  formData: PropTypes.object.isRequired,
  handleInputChange: PropTypes.func.isRequired,
  setIsLoading: PropTypes.func.isRequired,
  setIsEdit: PropTypes.func.isRequired,
  setFormData: PropTypes.func.isRequired,
  getMaps: PropTypes.func.isRequired,
  isLoading: PropTypes.bool.isRequired
}
